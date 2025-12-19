<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $slug = 'audit-logs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('causer_type')
                    ->label('Causer Type'),
                Forms\Components\TextInput::make('causer_id')
                    ->label('Causer ID'),
                Forms\Components\TextInput::make('subject_type')
                    ->label('Subject Type'),
                Forms\Components\TextInput::make('subject_id')
                    ->label('Subject ID'),
                Forms\Components\TextInput::make('description')
                    ->label('Description'),
                Forms\Components\KeyValue::make('properties')
                    ->label('Properties'),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Logged At'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->searchable()
                    ->default('System'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Aktivitas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Pada Data')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->badge(),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Changes')
                    ->formatStateUsing(function ($state) {
                        $attributes = $state['attributes'] ?? [];
                        $old = $state['old'] ?? [];

                        $count = count($attributes) + count($old);
                        return $count > 0 ? "$count Changes" : 'No Changes';
                    })
                    ->limit(20)
                    ->tooltip(fn($record) => json_encode($record->properties, JSON_PRETTY_PRINT)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('User')
                    ->relationship('causer', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Section::make('Activity Details')
                            ->schema([
                                Forms\Components\Placeholder::make('description')
                                    ->content(fn($record) => $record->description),
                                Forms\Components\Placeholder::make('subject')
                                    ->content(fn($record) => $record->subject_type . ' #' . $record->subject_id),
                                Forms\Components\Placeholder::make('causer')
                                    ->content(fn($record) => $record->causer?->name ?? 'System'),
                                Forms\Components\Placeholder::make('created_at')
                                    ->content(fn($record) => $record->created_at->toDateTimeString()),
                            ])->columns(2),
                        Forms\Components\Section::make('Changes')
                            ->schema([
                                Forms\Components\KeyValue::make('properties.old')
                                    ->label('Old Data')
                                    ->state(fn($record) => $record->properties['old'] ?? []),
                                Forms\Components\KeyValue::make('properties.attributes')
                                    ->label('New Data')
                                    ->state(fn($record) => $record->properties['attributes'] ?? []),
                            ])->columns(2)
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivityLogs::route('/'),
        ];
    }
}
