<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VendaResource\Pages;
use App\Filament\Resources\VendaResource\RelationManagers;
use App\Filament\Resources\VendaResource\RelationManagers\ContasReceberRelationManager;
use App\Filament\Resources\VendaResource\RelationManagers\ItensVendaRelationManager;
use App\Models\Cliente;
use App\Models\FormaPgmto;
use App\Models\Funcionario;
use App\Models\Venda;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VendaResource extends Resource
{
    protected static ?string $model = Venda::class;


    protected static ?string $navigationIcon = 'heroicon-s-shopping-cart';

    protected static ?string $navigationGroup = 'Saídas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                ->columns([
                    'xl' => 4,
                    '2xl' => 4,
                ])
                ->schema([
                    Forms\Components\Select::make('cliente_id')
                        ->label('Cliente')
                        ->default(1)
                        ->native(false)
                        ->searchable()
                        ->options(Cliente::all()->pluck('nome', 'id')->toArray())
                        ->required(),
                    Forms\Components\Select::make('funcionario_id')
                        ->default(1)
                        ->label('Funcionário')
                        ->native(false)
                        ->searchable()
                        ->options(Funcionario::all()->pluck('nome', 'id')->toArray())
                        ->required(),
                    Forms\Components\Select::make('forma_pgmto_id')
                        ->default(1)
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
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cliente.nome')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_venda')
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
                Tables\Actions\Action::make('Imprimir')
                ->url(fn (Venda $record): string => route('comprovanteNormal', $record))
                ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItensVendaRelationManager::class,
            ContasReceberRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendas::route('/'),
            'create' => Pages\CreateVenda::route('/create'),
            'edit' => Pages\EditVenda::route('/{record}/edit'),
        ];
    }



}
