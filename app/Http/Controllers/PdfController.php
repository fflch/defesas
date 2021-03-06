<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use App\Models\Agendamento;
use App\Models\Docente;
use App\Models\Banca;
use App\Models\Config;
use Carbon\Carbon;
use App\Utils\ReplicadoUtils;
use Uspdev\Replicado\Pessoa;

class PdfController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    //Bloco destinado aos documentos gerais
    public function documentosGerais(Agendamento $agendamento, $tipo){
        $this->authorize('admin');
        $configs = Config::orderbyDesc('created_at')->first();
        $agendamento->formatDataHorario($agendamento);
        $agendamento->nome_area = ReplicadoUtils::nomeAreaPrograma($agendamento->area_programa);
        if($tipo == 'placa'){
            $pdf = PDF::loadView('pdfs.documentos_gerais.placa', compact('agendamento'))->setPaper('a4', 'landscape');
            return $pdf->download('placa.pdf');
        }
        elseif($tipo == 'titulares'){
            $professores = Banca::where('agendamento_id',$agendamento->id)->where('tipo', 'Titular')->get();
            $bancas = $professores;
            $pdf = PDF::loadView("pdfs.documentos_gerais.$tipo", compact(['agendamento','professores','configs','bancas']));
            return $pdf->download("$tipo.pdf");
        }
        elseif($tipo == 'suplentes'){
            $configs = Config::setConfigOficioSuplente($agendamento);
            $professores = Banca::where('agendamento_id',$agendamento->id)->where('tipo', 'Suplente')->get();
            $bancas = $professores;
            $pdf = PDF::loadView("pdfs.documentos_gerais.$tipo", compact(['agendamento','professores','configs','bancas']));
            return $pdf->download("$tipo.pdf");
        }
        else{
            $professores = Banca::where('agendamento_id',$agendamento->id)->get();
            $bancas = $professores;
            $pdf = PDF::loadView("pdfs.documentos_gerais.$tipo", compact(['agendamento','professores','bancas','configs']));
            return $pdf->download("$tipo.pdf");
        }
    }

    //Bloco destinado aos documentos individuais
    public function documentosIndividuais(Agendamento $agendamento, Banca $banca, $tipo){
        $this->authorize('admin');
        $agendamento->formatDataHorario($agendamento);
        $agendamento->nome_area = ReplicadoUtils::nomeAreaPrograma($agendamento->area_programa);
        if($tipo == 'titular' or $tipo == 'declaracao'){
            $professores = Banca::where('agendamento_id',$agendamento->id)->where('tipo', 'Titular')->get();
            $professor = $banca;
            if($tipo == 'declaracao'){
                $configs = Config::setConfigDeclaracao($agendamento,$professores,$professor);
            }
            else{
                $configs = Config::orderbyDesc('created_at')->first();
            }
            $pdf = PDF::loadView("pdfs.documentos_bancas.$tipo", compact(['agendamento','professores','professor','configs']));
            $docente = Docente::dump($banca->codpes);
            if($docente == null){
                $nome = 'Professor';
            }
            else{
                $nome = $docente->nome;
            }
            return $pdf->download("$nome - $tipo.pdf");
        }
        elseif($tipo == 'suplente'){
            $configs = Config::setConfigOficioSuplente($agendamento);
            $professor = $banca;
            $pdf = PDF::loadView("pdfs.documentos_bancas.$tipo", compact(['agendamento','professor','configs']));
            $docente = Docente::dump($banca->codpes);
            if($docente == null){
                $nome = 'Professor';
            }
            else{
                $nome = $docente->nome;
            }
            return $pdf->download("$nome - $tipo.pdf");
        }
    }

    //Função destinada à geração de PDF PROEX
    public function proex(Agendamento $agendamento, Banca $banca, Request $request){
        $this->authorize('admin');
        $dados = $request;
        $agendamento->formatDataHorario($agendamento);
        $agendamento->nome_area = ReplicadoUtils::nomeAreaPrograma($agendamento->area_programa);
        $configs = Config::orderbyDesc('created_at')->first();
        $pdf = PDF::loadView("pdfs.recibos.proex", compact(['agendamento','banca','dados','configs']));
        $docente = Docente::dump($banca->codpes);
        if($docente == null){
            $nome = 'Professor';
        }
        else{
            $nome = $docente->nome;
        }
        return $pdf->download("$nome - proex.pdf");    
    }

    //Função destinada à geração de PDF PROAP
    public function proap(Agendamento $agendamento, Banca $banca, Request $request){
        $this->authorize('admin');
        $dados = $request;
        $agendamento->data = Carbon::parse($agendamento->data_horario)->format('m');
        $agendamento->nome_area = ReplicadoUtils::nomeAreaPrograma($agendamento->area_programa);
        $configs = Config::orderbyDesc('created_at')->first();
        $pdf = PDF::loadView("pdfs.recibos.proap", compact(['agendamento','banca','dados','configs']));
        $docente = Docente::dump($banca->codpes);
        if($docente == null){
            $nome = 'Professor';
        }
        else{
            $nome = $docente->nome;
        }
        return $pdf->download("$nome - proap.pdf");
    }

    //Função destinada à geração de PDF da passagem
    public function passagem(Agendamento $agendamento, Banca $banca, Request $request){
        $this->authorize('admin');
        $dados = $request;
        //$agendamento->formatDataHorario($agendamento);
        $agendamento->nome_area = ReplicadoUtils::nomeAreaPrograma($agendamento->area_programa);
        $configs = Config::orderbyDesc('created_at')->first();
        $pdf = PDF::loadView("pdfs.recibos.passagem", compact(['agendamento','banca','dados','configs']));
        $docente = Docente::dump($banca->codpes);
        if($docente == null){
            $nome = 'Professor';
        }
        else{
            $nome = $docente->nome;
        }
        return $pdf->download("$nome - passagem.pdf");
    }

    //Função destinada à geração de PDF da passagem via auxílio
    public function passagemAuxilio(Agendamento $agendamento, Banca $banca, Request $request){
        $this->authorize('admin');
        $dados = $request;
        $agendamento->formatDataHorario($agendamento);
        $configs = Config::orderbyDesc('created_at')->first();
        $pdf = PDF::loadView("pdfs.recibos.passagemAuxilio", compact(['agendamento','banca','dados','configs']));
        $docente = Docente::dump($banca->codpes);
        if($docente == null){
            $nome = 'Professor';
        }
        else{
            $nome = $docente->nome;
        }
        return $pdf->download("$nome - passagemAuxilio.pdf");
    }
}
