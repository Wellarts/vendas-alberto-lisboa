<?php

namespace App\Filament\Pages;

use App\Models\PDV;
use App\Models\VendaPDV;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentIcon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Illuminate\Support\Facades\Route;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\ContasPagar;
use App\Models\ContasReceber;
use Carbon\Carbon;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::pages.dashboard';

    //teste


    public function mount(): void
    {
        
        // Notification::make()
        //     ->title('ATENÇÃO')
        //     ->persistent()
        //     ->danger()
        //     ->body('Sua mensalidade está atrasada, regularize sua assinatura para evitar o bloqueio do sistema.
        //     PIX: 28708223831')
        //     ->actions([
        //         Action::make('Entendi')
        //             ->button()
        //             ->close(),
        //     ])
        //     ->send();

        PDV::whereNotIn('venda_p_d_v_id', VendaPDV::pluck('id'))->delete();

        //***********NOTIFICAÇÃO DE CONTAS A RECEBER*************
        $contasReceberVencer = ContasReceber::where('status','=','0')->get();
       // dd($contasReceberVencer);
        $hoje = Carbon::today();

        foreach ($contasReceberVencer as $cr) {
            $hoje = Carbon::today();
            $dataVencimento = Carbon::parse($cr->data_vencimento);
            $qtd_dias = $hoje->diffInDays($dataVencimento, false);
            if ($qtd_dias <= 3 && $qtd_dias > 0) {
                Notification::make()
                    ->title('ATENÇÃO: Conta a receber com vencimento próximo.')
                    ->body('Do cliente <b>' . $cr->cliente->nome. '</b> no valor de R$ <b>' . $cr->valor_parcela . '</b> com vencimento em <b>'.carbon::parse($cr->data_vencimento)->format('d/m/Y').'</b>.')
                    ->success()
                    ->persistent()
                    ->send();


            }
            if ($qtd_dias == 0) {
                Notification::make()
                    ->title('ATENÇÃO: Conta a receber com vencimento para hoje.')
                    ->body('Do cliente <b>' . $cr->cliente->nome. '</b> no valor de R$ <b>' . $cr->valor_parcela . '</b> com vencimento em <b>'.carbon::parse($cr->data_vencimento)->format('d/m/Y').'</b>.')
                    ->warning()
                    ->persistent()
                    ->send();


            }
            if ($qtd_dias < 0) {
                Notification::make()
                    ->title('ATENÇÃO: Conta a receber vencida.')
                    ->body('Do cliente <b>' . $cr->cliente->nome. '</b> no valor de R$ <b>' . $cr->valor_parcela . '</b> com vencimento em <b>'.carbon::parse($cr->data_vencimento)->format('d/m/Y').'</b>.')
                    ->danger()
                    ->persistent()
                    ->send();


            }
        }

        //***********NOTIFICAÇÃO DE CONTAS A PAGAR*************
        $contasPagarVencer = ContasPagar::where('status','=','0')->get();
        $hoje = Carbon::today();

        foreach ($contasPagarVencer as $cp) {
            $hoje = Carbon::today();
            $dataVencimento = Carbon::parse($cp->data_vencimento);
            $qtd_dias = $hoje->diffInDays($dataVencimento, false);
            if ($qtd_dias <= 3 && $qtd_dias > 0) {
                Notification::make()
                    ->title('ATENÇÃO: Conta a pagar com vencimento próximo.')
                    ->body('Do fornecedor <b>' . $cp->fornecedor->nome. '</b> no valor de R$ <b>' . $cp->valor_parcela . '</b> com vencimento em <b>'.carbon::parse($cp->data_vencimento)->format('d/m/Y').'</b>.')
                    ->success()
                    ->persistent()
                    ->send();


            }
            if ($qtd_dias == 0) {
                Notification::make()
                    ->title('ATENÇÃO: Conta a pagar com vencimento para hoje.')
                    ->body('Do fornecedor <b>' . $cp->fornecedor->nome. '</b> no valor de R$ <b>' . $cp->valor_parcela . '</b> com vencimento em <b>'.carbon::parse($cp->data_vencimento)->format('d/m/Y').'</b>.')
                    ->warning()
                    ->persistent()
                    ->send();


            }
            if ($qtd_dias < 0) {
                Notification::make()
                    ->title('ATENÇÃO: Conta a pagar vencida.')
                    ->body('Do fornecedor <b>' . $cp->fornecedor->nome. '</b> no valor de R$ <b>' . $cp->valor_parcela . '</b> com vencimento em <b>'.carbon::parse($cp->data_vencimento)->format('d/m/Y').'</b>.')
                    ->danger()
                    ->persistent()
                    ->send();


            }
        }
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ??
            static::$title ??
            __('filament-panels::pages/dashboard.title');
    }

    public static function getNavigationIcon(): ?string
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve('panels::pages.dashboard.navigation-item')
            ?? (Filament::hasTopNavigation() ? 'heroicon-m-home' : 'heroicon-o-home');
    }

    public static function routes(Panel $panel): void
    {
        Route::get(static::getRoutePath(), static::class)
            ->middleware(static::getRouteMiddleware($panel))
            ->withoutMiddleware(static::getWithoutRouteMiddleware($panel))
            ->name(static::getSlug());
    }

    public static function getRoutePath(): string
    {
        return static::$routePath;
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return Filament::getWidgets();
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    /**
     * @return int | string | array<string, int | string | null>
     */
    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getTitle(): string | Htmlable
    {
        return static::$title ?? __('filament-panels::pages/dashboard.title');
    }
}
