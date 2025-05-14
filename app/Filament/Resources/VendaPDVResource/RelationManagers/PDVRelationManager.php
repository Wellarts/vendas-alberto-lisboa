<?php

namespace App\Filament\Resources\VendaPDVResource\RelationManagers;

use App\Models\Produto;
use App\Models\VendaPDV;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PDVRelationManager extends RelationManager
{
    protected static string $relationship = 'PDV';

    protected static ?string $title = 'Itens da Venda PDV';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
               Forms\Components\TextInput::make('produto_id')
                    ->required(),
                Forms\Components\TextInput::make('qtd')
                    ->required(),
                Forms\Components\TextInput::make('sub_total')
                    ->numeric()
                    ->required()


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vendapdv_id')
            ->columns([
                Tables\Columns\TextColumn::make('venda_p_d_v_id')
                    ->label('Venda PDV'),
                Tables\Columns\TextColumn::make('produto.nome'),
                Tables\Columns\TextColumn::make('produto.codbar')
                    ->label('Código do Produto'),
                Tables\Columns\TextColumn::make('qtd')
                    ->summarize(Sum::make()->label('Qtd de Produtos')),
                Tables\Columns\TextColumn::make('acres_desc')
                    ->label('Acréscimo/Desconto'),
                Tables\Columns\TextColumn::make('sub_total')
                    ->summarize(Sum::make()->money('BRL')->label('Total'))
                    ->label('Sub-Total'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
              //  Tables\Actions\CreateAction::make(),
            ])
            ->actions([
              //  Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->before(function ($data, $record) {
                    $produto = Produto::find($record->produto_id);
                    $venda = VendaPDV::find($record->venda_p_d_v_id);
                    $venda->valor_total -= $record->sub_total;
                    $produto->estoque += ($record->qtd);
                    $venda->save();
                    $produto->save();
                })
                ->after(function () {
                    return redirect(request()->header('Referer'));
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            foreach ($records as $record) {
                                $produto = Produto::find($record->produto_id);
                                $venda = VendaPDV::find($record->venda_p_d_v_id);
                                $venda->valor_total -= $record->sub_total;
                                $produto->estoque += ($record->qtd);
                                $venda->save();
                                $produto->save();
                            }
                            
                        })
                        ->after(function () {
                                return redirect(request()->header('Referer'));
                          }),      
                        
                ]),
            ]);
    }
}
