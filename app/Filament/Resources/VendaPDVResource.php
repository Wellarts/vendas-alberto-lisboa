<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendaPDVResource\Pages;
use App\Filament\Resources\VendaPDVResource\RelationManagers;
use App\Filament\Resources\VendaPDVResource\RelationManagers\PDVRelationManager;
use App\Models\Cliente;
use App\Models\FormaPgmto;
use App\Models\Funcionario;
use App\Models\Venda;
use App\Models\VendaPDV;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendaPDVResource extends Resource
{
    protected static ?string $model = VendaPDV::class;

    protected static ?string $navigationIcon = 'heroicon-s-shopping-cart';

    protected static ?string $navigationGroup = 'Ponto de Venda';

    protected static ?string $navigationLabel = 'Vendas em PDV';

    protected static ?string $title = 'Vendas PDV';

    protected static ?int $navigationSort = 3;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                ->columns([
                    'xl' => 3,
                    '2xl' => 3,
                ])
                ->schema([
                    Forms\Components\Select::make('cliente_id')
                        ->label('Cliente')
                        ->native(false)
                        ->searchable()
                        ->options(Cliente::all()->pluck('nome', 'id')->toArray())
                        ->required(),
                    Forms\Components\Select::make('funcionario_id')
                        ->label('Funcionário')
                        ->native(false)
                        ->searchable()
                        ->options(Funcionario::all()->pluck('nome', 'id')->toArray())
                        ->required(),
                    Forms\Components\Select::make('forma_pgmto_id')
                        ->label('Forma de Pagamento')
                        ->native(false)
                        ->searchable()
                        ->options(FormaPgmto::all()->pluck('nome', 'id')->toArray())
                        ->required(),
                    Forms\Components\DatePicker::make('data_venda')
                        ->default(now())
                        ->required(),
                    Forms\Components\Textarea::make('obs')
                        ->columnSpan([
                            'xl' => 2,
                            '2xl' => 2,
                        ])
                        ->label('Observações'),
                    ])->columns([
                        'xl' => 2,
                        '2xl' => 2,
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_venda','desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Venda')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nome')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_venda')
                    ->label('Data da Venda')
                    ->searchable()
                    ->date('d/m/Y'),
                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Valor Total')
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
                Tables\Actions\EditAction::make()
                    ->modalHeading('Vendas PDV'),
                 Tables\Actions\Action::make('Imprimir')
                    ->url(fn (VendaPDV $record): string => route('comprovantePDV', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                  //  Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PDVRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendaPDVS::route('/'),
            'create' => Pages\CreateVendaPDV::route('/create'),
            'edit' => Pages\EditVendaPDV::route('/{record}/edit'),
        ];
    }
}
