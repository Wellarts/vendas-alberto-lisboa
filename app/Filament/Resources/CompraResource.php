<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompraResource\Pages;
use App\Filament\Resources\CompraResource\RelationManagers\ContasPagarRelationManager;
use App\Filament\Resources\CompraResource\RelationManagers\ItensCompraRelationManager;
use App\Models\Compra;
use App\Models\Fornecedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\Summarizers\Sum;

class CompraResource extends Resource
{
    protected static ?string $model = Compra::class;

    protected static ?string $navigationIcon = 'heroicon-s-shopping-cart';

    protected static ?string $navigationGroup = 'Entradas';

    protected static ?int $navigationSort = 7;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Section::make()
                    ->columns([
                        'xl' => 2,
                        '2xl' => 2,
                    ])
                    ->schema([
                        Forms\Components\Select::make('fornecedor_id')
                            ->label('Fornecedor')
                            ->native(false)
                            ->searchable()
                            ->options(Fornecedor::all()->pluck('nome', 'id')->toArray())
                            ->required(),
                        Forms\Components\DatePicker::make('data_compra')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('outros_custos'),
                        Forms\Components\Textarea::make('obs')
                            ->label('Observações'),
                        ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fornecedor.nome')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_compra')
                    ->searchable()
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('valor_total')
                    ->summarize(Sum::make()->money('BRL')->label('Total'))
                    ->money('BRL'),
                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->actions([
               Tables\Actions\EditAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItensCompraRelationManager::class,
            ContasPagarRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompras::route('/'),
            'create' => Pages\CreateCompra::route('/create'),
            'edit' => Pages\EditCompra::route('/{record}/edit'),
        ];
    }
}
