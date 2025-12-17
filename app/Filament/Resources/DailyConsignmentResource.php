<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyConsignmentResource\Pages;
use App\Filament\Resources\DailyConsignmentResource\Widgets\DailyConsignmentStats;
use App\Models\DailyConsignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DailyConsignmentResource extends Resource
{
    protected static ?string $model = DailyConsignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\Select::make('partner_id')
                    ->relationship('partner', 'name'),
                Forms\Components\TextInput::make('manual_partner_name'),
                Forms\Components\TextInput::make('product_name')
                    ->required(),
                Forms\Components\TextInput::make('initial_stock')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('base_price')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('markup_percentage')
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\TextInput::make('selling_price')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('remaining_stock')
                    ->numeric(),
                Forms\Components\TextInput::make('quantity_sold')
                    ->numeric(),
                Forms\Components\TextInput::make('total_revenue')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('total_profit')
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    ->required(),
                Forms\Components\Select::make('disposition')
                    ->options([
                        'returned' => 'Returned',
                        'donated' => 'Donated',
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\Select::make('input_by_user_id')
                    ->relationship('inputByUser', 'name')
                    ->required(),
            ])
            ->disabled(); // Make the entire form read-only
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('partner.name')
                    ->label('Partner')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity_sold')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_profit')
                    ->money()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date_from'),
                        Forms\Components\DatePicker::make('date_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            DailyConsignmentStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyConsignments::route('/'),
            // 'create' => Pages\CreateDailyConsignment::route('/create'), // Read-only, so no create
            'view' => Pages\ViewDailyConsignment::route('/{record}'),
        ];
    }
}
