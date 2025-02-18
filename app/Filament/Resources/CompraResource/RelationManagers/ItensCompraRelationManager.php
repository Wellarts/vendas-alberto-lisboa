<?php

namespace App\Filament\Resources\CompraResource\RelationManagers;

use App\Models\Compra;
use App\Models\FluxoCaixa;
use App\Models\ItensCompra;
use App\Models\Produto;
use App\Models\ProdutoFornecedor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use function Livewire\after;

class ItensCompraRelationManager extends RelationManager
{
    protected static string $relationship = 'ItensCompra';

    protected static ?string $recordTitleAttribute = 'compra_id';

    protected static ?string $title = 'Itens da Compra';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('id')
                    ->disabled(),
                Forms\Components\Hidden::make('compra_id')
                    ->default((function ($livewire): int {
                        return $livewire->ownerRecord->id;
                    })),

                // Forms\Components\Select::make('produto_id')
                //     ->relationship(name: 'produto', titleAttribute: 'nome')
                //     ->searchable(['nome', 'codbar'])
                //     //  ->options(Produto::all()->pluck('nome', 'id')->toArray())
                Forms\Components\Select::make('produto_id')
                            ->label('Produto')
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Produto::query()
                                    ->where('codbar', 'like', "%{$search}%")
                                    ->orWhere('nome', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function (Produto $product) {
                                        // Exibe código de barras e nome no select
                                        return [$product->id => "[{$product->codbar}] {$product->nome}"];
                                    });
                            })
                            ->getOptionLabelUsing(fn($value): ?string => Produto::where('id', $value)->first()?->nome)
                            ->autofocus()
                    ->createOptionForm([
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
                        Forms\Components\TextInput::make('lucratividade')
                            ->label('Lucratividade (%)')
                            ->default(0)
                    ])
                    ->disabled(fn($context) => $context == 'edit')
                    ->live(debounce: 200)
                    ->native(false)
                    ->required()
                    ->label('Produto')
                    ->afterStateUpdated(
                        function ($state, callable $set) {
                            $produto = Produto::find($state);
                            if ($produto) {
                                $set('valor_compra', $produto->valor_compra);
                            }
                        }
                    ),
                Forms\Components\TextInput::make('valor_compra')
                    ->numeric()
                    ->label('Valor Compra')
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(
                        function (Get $get, Set $set) {
                            $set('sub_total', (($get('qtd') * $get('valor_compra'))));
                        }
                    ),
                Forms\Components\TextInput::make('qtd')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $set('sub_total', (($get('qtd') * $get('valor_compra'))));
                    }),
                Forms\Components\TextInput::make('sub_total')
                    ->numeric()
                    ->readOnly()
                    ->label('Sub-Total'),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('compra_id')
            ->columns([
                Tables\Columns\TextColumn::make('produto.nome')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('qtd')
                    ->summarize(Sum::make()->label('Qtd de Produtos')),
                Tables\Columns\TextColumn::make('valor_compra')
                    ->money('BRL'),
                Tables\Columns\TextColumn::make('sub_total')
                    ->summarize(Sum::make()->money('BRL')->label('Total'))
                    ->money('BRL'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(fn($livewire) => $livewire->ownerRecord->status_caixa == 1)
                    ->after(function ($data) {
                        $produto = Produto::find($data['produto_id']);
                        $compra = Compra::find($data['compra_id']);
                        $produto->estoque += $data['qtd'];
                        $produto->valor_compra = $data['valor_compra'];
                        $produto->valor_venda = ($produto->valor_compra + ($data['valor_compra'] * ($produto->lucratividade / 100)));
                        $compra->valor_total += $data['sub_total'];
                        $compra->save();
                        $produto->save();

                        $prodFornecedor = [
                            'compra_id' => $data['compra_id'],
                            'produto_id' => $produto->id,
                            'qtd' => $data['qtd'],
                            'valor' => $data['valor_compra'],

                        ];
                       // dd($prodFornecedor);
                        ProdutoFornecedor::create($prodFornecedor);
                    })
                    ->label('Adicionar Produtos'),

                Tables\Actions\Action::make('fluxo_caixa')
                    ->label(('Lançar no Caixa'))
                    ->icon('heroicon-o-currency-dollar')
                    ->hidden(fn($livewire) => $livewire->ownerRecord->status_caixa == 1)
                    ->color('success')
                    ->action(function ($livewire) {
                        if ($livewire->ownerRecord->valor_total > 0) {
                            $addFluxoCaixa = [
                                'valor' => ($livewire->ownerRecord->valor_total * -1),
                                'tipo'  => 'DEBITO',
                                'obs'   => 'Pagamento de Compra nº: ' . $livewire->ownerRecord->id . '',
                            ];
                            $compra = Compra::find($livewire->ownerRecord->id);
                            $compra->status_caixa = 1;
                            $compra->save();


                            Notification::make()
                                ->title('Valor de R$' . $livewire->ownerRecord->valor_total . ' lançado no caixa com sucesso!')
                                ->success()
                                ->send();
                            FluxoCaixa::create($addFluxoCaixa);
                        } else {
                            Notification::make()
                                ->title('Atenção')
                                ->body('Valor da venda zerado. Adicione os produtos para depois lançar no caixa.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    })

                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-currency-dollar')


            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record) {
                        $produto = Produto::find($record->produto_id);
                        $idItemCompra = ItensCompra::find($record->id);
                        $compra = Compra::find($record->compra_id);

                        // dd($data['qtd'].'  -  '.- $idItemCompra->qtd);
                        $produto->estoque += ($data['qtd'] - $idItemCompra->qtd);
                        $produto->valor_compra = $record->valor_compra;
                        $produto->valor_venda = ($produto->valor_compra + ($record->valor_compra * ($produto->lucratividade / 100)));
                        $compra->valor_total += ($data['sub_total'] - $idItemCompra->sub_total);
                        // dd($data['sub_total'], $idItemCompra->sub_total,  $compra->valor_total);
                        $compra->save();
                        $produto->save();
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($data, $record) {
                        $produto = Produto::find($record->produto_id);
                        $compra = Compra::find($record->compra_id);
                        $compra->valor_total -= $record->sub_total;
                        $produto->estoque -= ($record->qtd);
                        $produto->save();
                        $compra->save();

                        $prodFornecedor = [
                            'compra_id' => $record->compra_id,
                            'produto_id' => $produto->id,

                        ];
                        ProdutoFornecedor::destroy($prodFornecedor);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
