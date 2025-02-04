<?php

namespace App\Filament\Resources;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\Filament\Resources\ProdutoResource\Pages;
use App\Filament\Resources\ProdutoResource\RelationManagers;
use App\Filament\Resources\ProdutoResource\RelationManagers\ProdutoFornecedorRelationManager;
use App\Models\Produto;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-s-shopping-bag';

    protected static ?string $navigationGroup = 'Cadastros';

    protected static ?string $label = 'Produtos/Serviços';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Cadastro')
                    ->columns([
                        'xl' => 3,
                        '2xl' => 3,
                    ])
                    ->schema([
                        Forms\Components\ToggleButtons::make('tipo')
                            ->label('Tipo')
                            ->default(1)
                            ->columnSpanFull()
                            ->options([
                                '1' => 'Produto',
                                '2' => 'Serviço',

                            ])
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state == 1) {
                                    $set('lucratividade', 0);
                                } elseif ($state == 2) {
                                    $set('lucratividade', 100);
                                }
                            })

                            ->grouped(),
                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('codbar')
                            ->label('Código de Barras')
                            ->hidden(function (Get $get) {
                                if ($get('tipo') == 1) {
                                    return false;
                                } elseif ($get('tipo') == 2) {
                                    return true;
                                }
                            })
                            ->required(false),
                        Forms\Components\TextInput::make('estoque')
                            ->numeric()
                            ->integer()
                            ->hidden(function (Get $get) {
                                if ($get('tipo') == 1) {
                                    return false;
                                } elseif ($get('tipo') == 2) {
                                    return true;
                                }
                            }),
                        Forms\Components\TextInput::make('valor_compra')
                            ->label('Valor Compra')
                            ->hidden(function (Get $get) {
                                if ($get('tipo') == 1) {
                                    return false;
                                } elseif ($get('tipo') == 2) {
                                    return true;
                                }
                            })
                            ->numeric()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('valor_venda', ((((float)$get('valor_compra') * (float)$get('lucratividade')) / 100) + (float)$get('valor_compra')));
                            }),
                        Forms\Components\TextInput::make('lucratividade')
                            ->label('Lucratividade (%)')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('valor_venda', ((((float)$get('valor_compra') * (float)$get('lucratividade')) / 100) + (float)$get('valor_compra')));
                            }),
                        Forms\Components\TextInput::make('valor_venda')
                            ->label('Valor Venda')
                            ->numeric()
                            // ->disabled(),
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                if ($get('tipo') == 1) {
                                    $set('lucratividade', (((((float)$get('valor_venda') - (float)$get('valor_compra')) / (float)$get('valor_compra')) * 100)));
                                }
                            }),
                        FileUpload::make('foto')
                            ->label('Fotos')
                            ->columnSpanFull()
                            ->panelLayout('grid')
                            ->downloadable()
                            ->multiple()
                            ->maxSize(4096)
                            ->maxFiles(3)
                            ->hidden(function (Get $get) {
                                if ($get('tipo') == 1) {
                                    return false;
                                } elseif ($get('tipo') == 2) {
                                    return true;
                                }
                            })
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'success',
                        '2' => 'warning',
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state == 1) {
                            return 'Produto';
                        }
                        if ($state == 2) {
                            return 'Serviço';
                        }
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('codbar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estoque')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valor_compra')
                    ->money('BRL'),
                Tables\Columns\TextColumn::make('lucratividade')
                    ->label('Lucratividade (%)'),
                Tables\Columns\TextColumn::make('valor_venda')
                    ->money('BRL'),
                ImageColumn::make('foto')
                    ->label('Fotos')
                    ->alignCenter()
                    ->circular()
                    ->stacked()
                    ->limit(2)
                    ->limitedRemainingText(),
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
                ExportBulkAction::make(),



            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProdutoFornecedorRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),

        ];
    }
}
