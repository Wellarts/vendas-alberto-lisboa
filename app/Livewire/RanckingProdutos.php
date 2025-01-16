<?php

namespace App\Livewire;

use App\Models\VwSomaQuantidadeProduto;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RanckingProdutos extends BaseWidget
{

    

    public function table(Table $table): Table
    {
        return $table
            ->query(
                VwSomaQuantidadeProduto::query()
            )
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->sortable()
                    ->searchable()
                    ->label('Produto'),
                Tables\Columns\TextColumn::make('total_vendido_qtd')
                    ->sortable()
                    ->searchable()
                    ->label('Qtd'),
                Tables\Columns\TextColumn::make('total_vendido_valor')
                    ->sortable()
                    ->searchable()
                    ->label('Valor Vendido'),
                Tables\Columns\TextColumn::make('total_vendido_custo')
                    ->sortable()
                    ->searchable()
                    ->label('Valor Custo'),
            ]);
    }
}
