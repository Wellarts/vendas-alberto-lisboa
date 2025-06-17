<?php

namespace App\Filament\Pages;

use App\Models\VendaPDV;
use App\Models\PDV;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;

class LucratividadePDV extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.lucratividade-p-d-v';

    protected static ?string $navigationGroup = 'Consultas';

    protected static ?string $navigationLabel = 'Lucratividade PDV';

    protected static ?string $title = 'Lucratividade PDV';

    protected static ?int $navigationSort = 18;

    // public static function shouldRegisterNavigation(): bool
    // {
    //      /** @var \App\Models\User */
    //      $authUser =  auth()->user();

    //      if ($authUser->hasRole('TI')) {
    //          return true;
    //      } else {
    //          return false;
    //      }
    // }


    public function mount()
    {

        $vendas = VendaPDV::all();

        foreach ($vendas as $venda) {
                $custo_venda = $venda->itensVenda()->sum('total_custo_atual');
                $venda->lucro_venda = ($venda->valor_total - $custo_venda);
                $venda->save();

        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_venda','desc')
            ->query(VendaPDV::query())
          //  ->defaultGroup('data_venda','year')
            ->columns([
                TextColumn::make('id')
                    ->alignCenter()
                    ->label('Venda'),
                TextColumn::make('cliente.nome')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('data_venda')
                    ->date('d/m/Y')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('itens_venda_sum_total_custo_atual')->sum('itensVenda', 'total_custo_atual')
                    ->badge()
                    ->alignCenter()
                    ->label('Custo Produtos')
                    ->money('BRL')
                    ->color('danger'),
                TextColumn::make('valor_total')
                    ->summarize(Sum::make()->money('BRL')->label('Total'))
                    ->badge()
                    ->alignCenter()
                    ->label('Valor da Venda')
                    ->money('BRL')
                    ->color('warning'),
                TextColumn::make('lucro_venda')
                    ->summarize(Sum::make()->money('BRL')->label('Total'))
                    ->badge()
                    ->alignCenter()
                    ->label('Lucro por Venda')
                    ->money('BRL')
                    ->color('success')
                    ->getStateUsing(function (VendaPDV $record): float {
                        $custoProdutos = $record->itensVenda()->sum('total_custo_atual');
                        return ($record->valor_total - $custoProdutos);
                    })


            ])
            ->filters([
                SelectFilter::make('cliente')->relationship('cliente', 'nome'),

                Filter::make('data_vencimento')
                    ->form([
                        DatePicker::make('venda_de')
                            ->label('Data da Venda de:'),
                        DatePicker::make('venda_ate')
                            ->label('Data da Venda até:'),
                    ])
                    ->query(function ($query, array $data) {
                        $query
                            ->when(
                                $data['venda_de'],
                                fn ($query) => $query->whereDate('data_venda', '>=', $data['venda_de'])
                            )
                            ->when(
                                $data['venda_ate'],
                                fn ($query) => $query->whereDate('data_venda', '<=', $data['venda_ate'])
                            );
                    })
            ]);
    }


}
