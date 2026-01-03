
/**
 * Thermal Printer Service
 * Handles receipt generation for 58mm/80mm thermal printers.
 * Supports:
 * 1. HTML/CSS Printing (Bridge via Browser)
 * 2. ESC/POS Commands (Raw) - Ready for WebUSB/Bluetooth
 */
export class ThermalPrinter {
    constructor(config = {}) {
        this.width = config.width || '58mm'; // '58mm' or '80mm'
        this.storeName = config.storeName || 'POSITA Coffee';
        this.storeAddress = config.storeAddress || 'Ruko Grand Wisata AA 10/24';
        this.storePhone = config.storePhone || '0812-3456-7890';
        this.footerMessage = config.footerMessage || 'Terima Kasih!\nSilahkan Datang Kembali';
    }

    /**
     * Print transaction via Browser Print Dialog (Optimized for Thermal)
     * @param {Object} transaction 
     */
    printBrowser(transaction) {
        const printWindow = window.open('', 'PRINT', 'height=600,width=400');

        if (!printWindow) {
            alert('Popup blocked! Please allow popups for printing.');
            return;
        }

        printWindow.document.write('<html><head><title>Print Receipt</title>');
        printWindow.document.write(this._getStyles());
        printWindow.document.write('</head><body>');
        printWindow.document.write(this._generateHtml(transaction));
        printWindow.document.write('</body></html>');

        printWindow.document.close(); // necessary for IE >= 10
        printWindow.focus(); // necessary for IE >= 10

        // Wait for styles/images to load
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
    }

    /**
     * Generate raw ESC/POS commands
     * Useful for Direct Printing via WebBluetooth / Serial API
     * @param {Object} transaction 
     * @returns {Uint8Array}
     */
    generateEscPos(transaction) {
        const encoder = new TextEncoder();
        let commands = [];

        // Helpers
        const ESC = '\x1B';
        const GS = '\x1D';
        const LF = '\x0A';

        // Init
        commands.push(ESC + '@'); // Initialize

        // Align Center
        commands.push(ESC + 'a' + '\x01');

        // Store Header
        commands.push(ESC + '!' + '\x10'); // Double Height
        commands.push(this.storeName + LF);
        commands.push(ESC + '!' + '\x00'); // Normal text
        commands.push(this.storeAddress + LF);
        commands.push(this.storePhone + LF);
        commands.push(LF);

        // Transaction Info
        commands.push(ESC + 'a' + '\x00'); // Align Left
        commands.push(`Date: ${new Date().toLocaleString('id-ID')}` + LF);
        commands.push(`Reff: #${transaction.id || 'Unknown'}` + LF);
        commands.push('--------------------------------' + LF);

        // Items
        transaction.cart.forEach(item => {
            commands.push(`${item.name}` + LF);

            // Layout: 2 x 15000       30000
            const qtyPrice = `${item.quantity} x ${item.price}`;
            const total = `${item.quantity * item.price}`;

            // Simple spacing (assuming 32 chars width for 58mm)
            const spaceNeeded = 32 - qtyPrice.length - total.length;
            const spaces = ' '.repeat(Math.max(1, spaceNeeded));

            commands.push(qtyPrice + spaces + total + LF);
        });

        commands.push('--------------------------------' + LF);

        // Totals (Align Right ideally, but Keep Left for simple logic)
        commands.push(`Subtotal: ${transaction.subtotal}` + LF);
        if (transaction.tax > 0) {
            commands.push(`Tax:      ${transaction.tax}` + LF);
        }
        commands.push(ESC + '!' + '\x08'); // Emphasized
        commands.push(`TOTAL:    ${transaction.total}` + LF);
        commands.push(ESC + '!' + '\x00');

        commands.push(`Cash:     ${transaction.amountPaid}` + LF);
        commands.push(`Change:   ${transaction.change}` + LF);
        commands.push(LF);

        // Footer
        commands.push(ESC + 'a' + '\x01'); // Center
        commands.push(this.footerMessage + LF);
        commands.push(LF + LF + LF);

        // Cut Paper
        commands.push(GS + 'V' + '\x41' + '\x00');

        return encoder.encode(commands.join(''));
    }

    /**
     * Generate HTML Layout
     */
    _generateHtml(tx) {
        let itemsHtml = '';

        tx.cart.forEach(item => {
            itemsHtml += `
                <div class="item">
                    <div class="item-name">${item.name}</div>
                    <div class="item-details">
                        <span>${item.quantity} x ${this._formatCurrency(item.price)}</span>
                        <span>${this._formatCurrency(item.quantity * item.price)}</span>
                    </div>
                </div>
            `;
        });

        const dateStr = new Date().toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short'
        });

        return `
            <div class="receipt">
                <div class="header">
                    <h1 class="store-name">${this.storeName}</h1>
                    <p>${this.storeAddress}</p>
                    <p>${this.storePhone}</p>
                </div>
                
                <div class="meta">
                    <p>Date: ${dateStr}</p>
                    <p>No: #${tx.id || Math.floor(Date.now() / 1000)}</p>
                </div>

                <div class="divider">--------------------------------</div>
                
                <div class="items">
                    ${itemsHtml}
                </div>

                <div class="divider">--------------------------------</div>

                <div class="totals">
                    <div class="row">
                        <span>Subtotal</span>
                        <span>${this._formatCurrency(tx.subtotal)}</span>
                    </div>
                    ${tx.tax > 0 ? `
                    <div class="row">
                        <span>Pajak</span>
                        <span>${this._formatCurrency(tx.tax)}</span>
                    </div>` : ''}
                    <div class="row total-row">
                        <span>TOTAL</span>
                        <span>${this._formatCurrency(tx.total)}</span>
                    </div>
                </div>

                <div class="divider">--------------------------------</div>

                <div class="payment">
                    <div class="row">
                        <span>Tunai</span>
                        <span>${this._formatCurrency(tx.amountPaid)}</span>
                    </div>
                    <div class="row">
                        <span>Kembali</span>
                        <span>${this._formatCurrency(tx.change)}</span>
                    </div>
                </div>

                <div class="footer">
                    <p>${this.footerMessage.replace(/\n/g, '<br>')}</p>
                    <p class="brand">Powered by Posita</p>
                </div>
            </div>
        `;
    }

    /**
     * CSS specific for thermal printing
     */
    _getStyles() {
        return `
            <style>
                @page {
                    margin: 0;
                    size: ${this.width} auto; 
                }
                body {
                    margin: 0;
                    padding: 8px;
                    font-family: 'Courier New', monospace;
                    font-size: 12px;
                    line-height: 1.2;
                    background: #fff;
                    color: #000;
                    width: ${this.width};
                }
                .receipt {
                    width: 100%;
                }
                .header, .footer {
                    text-align: center;
                }
                .store-name {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 0 0 5px 0;
                    text-transform: uppercase;
                }
                .header p, .footer p {
                    margin: 2px 0;
                }
                .meta {
                    margin-top: 10px;
                    font-size: 10px;
                }
                .meta p {
                    margin: 2px 0;
                }
                .divider {
                    margin: 8px 0;
                    text-align: center;
                    white-space: nowrap;
                    overflow: hidden;
                }
                .item {
                    margin-bottom: 5px;
                }
                .item-name {
                    font-weight: bold;
                }
                .item-details {
                    display: flex;
                    justify-content: space-between;
                }
                .totals, .payment {
                    margin-top: 5px;
                }
                .row {
                    display: flex;
                    justify-content: space-between;
                    margin: 2px 0;
                }
                .total-row {
                    font-weight: bold;
                    font-size: 14px;
                    margin-top: 5px;
                }
                .footer {
                    margin-top: 20px;
                    font-size: 10px;
                }
                .brand {
                    margin-top: 10px;
                    font-size: 9px;
                    color: #666;
                }
            </style>
        `;
    }

    _formatCurrency(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(value);
    }
}
