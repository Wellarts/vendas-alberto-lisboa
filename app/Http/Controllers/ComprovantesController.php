<?php

namespace App\Http\Controllers;

use App\Models\Venda;
use App\Models\VendaPDV;
use Illuminate\Http\Request;

class ComprovantesController extends Controller
{
    public function geraPdf($id)
    
    {

        $vendas = Venda::find($id);
      //  $registros = Venda::with('categoria')->get();    
   //   dd($vendas->formaPgmto);
       
        
        
     //  return pdf::loadView('pdf.venda', compact(['vendas']))->stream();

       return view('pdf.venda', compact(['vendas']));
    }

    public function geraPdfPDV($id)
    
    {

        $vendas = VendaPDV::find($id);
      //  $registros = Venda::with('categoria')->get();    
   //   dd($vendas->formaPgmto);
       
        
        
     //  return pdf::loadView('pdf.venda', compact(['vendas']))->stream();

       return view('pdf.venda', compact(['vendas']));
    }
}
