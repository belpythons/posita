# P17 — Laporan Variance / Kebocoran Profit

**Fase:** F4 · **WP:** 4.6 · **Effort:** ± 1 pekan · **Prasyarat:** P05, P06

---

## PERAN
Data engineer yang membangun **laporan paling berharga di seluruh produk**.
Ini yang membuat pengguna berkata "aplikasi ini membayar dirinya sendiri".

## KONTEKS
Riset menunjukkan: POS melaporkan *apa yang terjual*, inventory melaporkan
*apa yang tersisa*, tapi hampir tidak ada yang menyandingkan keduanya. Selisih
antara pemakaian teoretis dan aktual adalah kebocoran profit yang tidak
pernah muncul di laporan manapun — dan besarnya sering 3–5% dari omzet,
persis sebesar selisih antara bisnis yang untung dan yang tutup.

## TUJUAN
Laporan yang menjawab pertanyaan paling penting pemilik kedai:
**"Ke mana untung saya pergi?"**

## RUANG LINGKUP
1. Perhitungan pemakaian teoretis dari penjualan × resep
2. Perhitungan pemakaian aktual dari ledger stok
3. Variance = aktual − teoretis − waste tercatat
4. Nilai variance dalam rupiah
5. Laporan per bahan, per periode, dengan tren
6. Analisis dugaan penyebab (rule-based, bukan AI)
7. Ambang alert + integrasi notifikasi
8. Tabel agregat untuk performa
9. Ekspor CSV/PDF

## DI LUAR LINGKUP
- ❌ Machine learning / prediksi
- ❌ Rekomendasi otomatis perubahan resep
- ❌ Deteksi pencurian otomatis (hanya menandai anomali)

---

## SPESIFIKASI

### Perhitungan

```
Untuk setiap bahan, dalam periode [start, end], di outlet X:

pemakaian_teoretis = Σ ( qty_produk_terjual × qty_bahan_per_resep_versi_saat_itu )
                     termasuk modifier yang punya resep

pemakaian_aktual   = stok_awal
                     + Σ pembelian
                     + Σ transfer_masuk
                     + Σ produksi_masuk
                     − stok_akhir
                     − Σ transfer_keluar
                     − Σ retur_supplier

waste_tercatat     = Σ waste_logs untuk bahan ini di periode ini

variance           = pemakaian_aktual − pemakaian_teoretis − waste_tercatat
variance_percent   = variance / pemakaian_teoretis × 100
variance_value     = variance × biaya_rata2_periode
```

**Penting:** gunakan **versi resep yang berlaku saat transaksi terjadi**,
bukan versi terbaru. Resep berubah; laporan historis harus konsisten.

### Klasifikasi

| Variance % | Status | Warna |
|---|---|---|
| ≤ 1% | Sangat baik | 🟢 |
| 1–3% | Wajar | 🟢 |
| 3–5% | Perlu perhatian | 🟠 |
| 5–10% | Bermasalah | 🔴 |
| > 10% | Serius | 🔴🔴 |
| Negatif | Anomali (cek resep/opname) | 🟣 |

**Variance negatif** (aktual < teoretis) juga mencurigakan: kemungkinan
resep salah (takaran di sistem lebih besar dari kenyataan), opname salah,
atau penerimaan barang tidak tercatat.

### Analisis dugaan penyebab (rule-based)

```php
final class VarianceCauseAnalyzer
{
    /** @return array<VarianceCause> */
    public function analyze(IngredientVariance $variance, Period $period): array;
}
```

Aturan yang harus diimplementasikan:

| Kondisi | Dugaan penyebab |
|---|---|
| Tidak ada waste log dalam periode padahal bahan rawan waste (kopi, susu) | "Waste tidak dicatat" |
| Variance tinggi hanya di shift tertentu | "Terkonsentrasi di shift {X} — cek prosedur atau personel" |
| Variance tinggi pada bahan yang dipakai banyak resep | "Takaran tidak konsisten — cek kalibrasi alat" |
| Ada void setelah item disiapkan | "N transaksi dibatalkan setelah dibuat (Rp X)" |
| Opname terakhir > 30 hari lalu | "Data stok mungkin sudah menyimpang — lakukan opname" |
| Variance melonjak setelah tanggal tertentu | "Perubahan mulai {tanggal} — apa yang berubah saat itu?" |
| Variance negatif konsisten | "Resep mungkin lebih besar dari takaran sebenarnya" |
| Bahan punya expiry & banyak batch kedaluwarsa | "Overstock — kurangi jumlah pembelian" |

Dugaan penyebab ditampilkan sebagai **petunjuk untuk diselidiki**, bukan
tuduhan. Bahasa harus hati-hati — menuduh karyawan mencuri berdasarkan data
yang belum tentu akurat bisa merusak hubungan kerja pengguna.

### Tampilan laporan

```
LAPORAN KEBOCORAN — 1–30 September 2026
Kopi Senja · Cabang Utama

RINGKASAN
  Nilai kebocoran tidak terjelaskan       Rp 417.800
  Setara                                  4,2% dari omzet
  Potensi tambahan laba jika ditekan ke 1%  Rp 318.000/bulan

DETAIL PER BAHAN
┌────────────────┬─────────┬────────┬────────┬──────────┬───────────┬────────┐
│ Bahan          │Teoretis │ Waste  │ Aktual │ Variance │  Nilai    │ Status │
├────────────────┼─────────┼────────┼────────┼──────────┼───────────┼────────┤
│ Biji Arabika   │ 12,4 kg │ 0,3 kg │14,1 kg │ +1,4 kg  │Rp 252.000 │🔴 11,3%│
│ Susu UHT       │  186 L  │ 2,0 L  │ 194 L  │ +6,0 L   │Rp  96.000 │🟠  3,2%│
│ Cup 16oz       │  1.240  │   12   │ 1.310  │ +58      │Rp  63.800 │🟠  4,7%│
│ Gula Aren      │ 31,0 L  │ 0,4 L  │31,6 L  │ +0,2 L   │Rp   6.000 │🟢  0,6%│
│ Sirup Vanilla  │  8,2 L  │ 0,1 L  │ 8,0 L  │ −0,3 L   │−Rp  9.000 │🟣 −3,7%│
└────────────────┴─────────┴────────┴────────┴──────────┴───────────┴────────┘

BIJI ARABIKA — dugaan penyebab
  • Waste kalibrasi tidak dicatat
    Shift pagi tidak punya catatan waste dalam 30 hari terakhir.
    Kalibrasi espresso biasanya membuang 100–300 g/hari.
    Estimasi: ± 0,9 kg (Rp162.000)
  • 3 transaksi dibatalkan setelah minuman dibuat (Rp66.000)
  • Opname terakhir 47 hari lalu — data stok mungkin sudah menyimpang

  Saran: catat waste kalibrasi setiap pagi, lalu lihat laporan ini lagi
  bulan depan.

TREN 6 BULAN
  Apr  2,1% ▓▓
  Mei  2,4% ▓▓
  Jun  3,8% ▓▓▓
  Jul  3,2% ▓▓▓
  Ags  4,9% ▓▓▓▓
  Sep  4,2% ▓▓▓▓        ⚠️ naik sejak Juni
```

### Performa

Perhitungan ini berat (menjumlah ribuan order × resep). Wajib:

```sql
-- Tabel agregat, diisi job harian
ingredient_usage_daily (
  id, tenant_id, outlet_id, ingredient_id, date,
  theoretical_quantity, actual_quantity, waste_quantity,
  variance_quantity, average_cost, variance_value,
  order_count,
  UNIQUE(outlet_id, ingredient_id, date)
)
```

- Job harian `AggregateIngredientUsage` mengisi tabel ini untuk hari
  kemarin.
- Laporan periode = agregasi dari tabel harian (cepat).
- Laporan hari berjalan dihitung on-demand (data lebih sedikit).
- Command `posita:variance:rebuild --from=... --to=...` untuk backfill.

Target: laporan 30 hari untuk 50 bahan < 2 detik.

### Alert

Event `VarianceThresholdExceeded` di-dispatch saat:
- Variance bulanan suatu bahan > ambang (default 5%)
- Total nilai kebocoran bulanan > ambang (default Rp200.000)
- Variance meningkat > 2 poin persen dibanding bulan lalu

Terhubung ke sistem notifikasi (P14).

---

## ACCEPTANCE CRITERIA

- [ ] Pemakaian teoretis terhitung benar dari penjualan × resep versi saat
      transaksi
- [ ] Pemakaian aktual terhitung benar dari ledger stok
- [ ] Variance dan nilainya dalam rupiah akurat
- [ ] Skenario uji: jual 100 kopi (resep 18 g = 1,8 kg teoretis), stok
      berkurang 2,1 kg, waste tercatat 0,1 kg → variance = +0,2 kg
- [ ] Variance negatif ditandai sebagai anomali
- [ ] Minimal 6 aturan dugaan penyebab terimplementasi dan menghasilkan
      keluaran yang relevan
- [ ] Laporan 30 hari untuk 50 bahan < 2 detik
- [ ] Job agregasi harian berjalan dan idempoten (aman dijalankan ulang)
- [ ] Backfill `posita:variance:rebuild` menghasilkan angka identik
- [ ] Tren 6 bulan ditampilkan
- [ ] Event alert ter-dispatch pada ambang yang benar
- [ ] Ekspor CSV & PDF berfungsi

---

## TESTING WAJIB

```php
// tests/Unit/Reporting/VarianceCalculatorTest.php
it('computes theoretical usage from sales and recipes');
it('uses the recipe version effective at transaction time');
it('includes modifier recipe usage');
it('computes actual usage from the stock ledger');
it('subtracts recorded waste from variance');
it('flags negative variance as an anomaly');
it('values variance using the period average cost');

// tests/Unit/Reporting/VarianceCauseAnalyzerTest.php
it('suggests unrecorded waste when no waste logs exist');
it('identifies shift concentration');
it('flags a stale stock take');
it('reports voids after preparation');
it('suggests recipe error on consistent negative variance');

// tests/Feature/Reporting/VarianceReportTest.php
it('matches a hand-calculated scenario end to end');
it('aggregates daily rows into a period report');
it('is idempotent when the aggregation job runs twice');
it('rebuilds identical figures via backfill');
it('completes a 30-day 50-ingredient report in under 2 seconds');
```

**Wajib:** buat satu skenario integrasi yang dihitung manual di atas kertas,
lalu buktikan sistem menghasilkan angka yang sama persis.

---

## PERINTAH
```bash
vendor/bin/pint && vendor/bin/phpstan analyse
php artisan test --filter=Variance
php artisan posita:variance:rebuild --outlet=1 --from=2026-08-01 --to=2026-08-31
```

---

## CATATAN

⚠️ **Ini fitur signature produk.** Kalau angkanya salah, kepercayaan pada
seluruh aplikasi hilang. Verifikasi dengan perhitungan manual.

⚠️ **Bahasa laporan harus hati-hati.** Jangan pernah menulis "karyawan
mencuri". Tulis "ada 1,4 kg yang tidak terjelaskan; ini beberapa
kemungkinannya". Data bisa salah, dan tuduhan yang salah merusak hubungan
kerja pengguna.

⚠️ **Laporan ini butuh data yang lengkap untuk berguna.** Jika pengguna
tidak mencatat waste dan tidak pernah opname, variance akan tinggi dan tidak
bermakna. Sertakan "skor kesiapan data" yang menjelaskan apa yang perlu
dilengkapi agar laporan akurat.

⚠️ **Jangan hitung on-the-fly untuk periode panjang.** Tabel agregat harian
wajib. Tanpa itu, laporan 90 hari akan timeout.
