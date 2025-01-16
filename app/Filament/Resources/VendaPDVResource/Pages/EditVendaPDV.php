<?php

namespace App\Filament\Resources\VendaPDVResource\Pages;

use App\Filament\Resources\VendaPDVResource;
use App\Models\PDV;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVendaPDV extends EditRecord
{
    protected static string $resource = VendaPDVResource::class;

    protected static ?string $title = 'Venda PDV';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
              ->disabled(fn($record) => PDV::where('venda_p_d_v_id',$record->id)->count()),
              
             
        ];
    }
}
