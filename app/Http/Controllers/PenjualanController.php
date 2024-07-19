<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Member;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\Setting;
use Illuminate\Http\Request;
use PDF;

class PenjualanController extends Controller
{
    public function index()
    {
        return view('penjualan.index');
    }

    public function data()
    {
        $penjualan = Penjualan::with('member')->orderBy('id_penjualan', 'desc')->get();

        return datatables()
            ->of($penjualan)
            ->addIndexColumn()
            ->addColumn('total_item', function ($penjualan) {
                return format_uang($penjualan->total_item);
            })
            ->addColumn('total_harga', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->total_harga);
            })
            ->addColumn('bayar', function ($penjualan) {
                return 'Rp. '. format_uang($penjualan->bayar);
            })
            ->addColumn('tanggal', function ($penjualan) {
                return tanggal_indonesia($penjualan->created_at, false);
            })
            ->addColumn('kode_member', function ($penjualan) {
                $member = $penjualan->member->kode_member ?? '';
                return '<span class="label label-success">'. $member .'</spa>';
            })
            ->editColumn('diskon', function ($penjualan) {
                return $penjualan->diskon . '%';
            })
            ->editColumn('status', function ($penjualan) {
                if($penjualan->success == 1) {
                    return 'Selesai';
                } else {
                    return 'Belum';
                }
            })
            ->editColumn('kasir', function ($penjualan) {
                return $penjualan->user->name ?? '';
            })
            ->addColumn('aksi', function ($penjualan) {
                if ($penjualan->success == 0) {

                    return '
                    <div class="btn-group">
                        <button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                        <button onclick="editPenjualan(`' . route('transaksi.transaksiAktifBaru', $penjualan->id_penjualan) . '`)" class="btn btn-xs btn-success btn-flat"><i class="fa fa-edit"></i></button>
                        <button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                    </div>
                    ';
                } else {

                    return '
                    <div class="btn-group">
                        <button onclick="showDetail(`'. route('penjualan.show', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-info btn-flat"><i class="fa fa-eye"></i></button>
                        <button onclick="deleteData(`'. route('penjualan.destroy', $penjualan->id_penjualan) .'`)" class="btn btn-xs btn-danger btn-flat"><i class="fa fa-trash"></i></button>
                    </div>
                    ';
                }
                
            })
            ->rawColumns(['aksi', 'kode_member'])
            ->make(true);
    }
 
    public function create()
    {
        $penjualan = new Penjualan();
        $penjualan->id_member = null;
        $penjualan->total_item = 0;
        $penjualan->total_harga = 0;
        $penjualan->diskon = 0;
        $penjualan->bayar = 0;
        $penjualan->diterima = 0;
        $penjualan->success = 0;
        $penjualan->id_user = auth()->id();
        $penjualan->save();

        session(['id_penjualan' => $penjualan->id_penjualan]);
        return redirect()->route('transaksi.index');
    }

    public function store(Request $request)
    {
        $pembayaranKurang = $request->diterima < $request->bayar && $request->diterima != 0;
        $penjualan = Penjualan::findOrFail($request->id_penjualan);
        $penjualan->id_member = $request->id_member;
        $penjualan->total_item = $request->total_item;
        $penjualan->total_harga = $request->total;
        $penjualan->success = 1;
        $diskonString = preg_replace('/\D/', '', $request->diskon);
        $diskonInt = (int)$diskonString;
        $penjualan->diskon = $diskonInt;
        $penjualan->bayar = $request->bayar;
        $penjualan->diterima = $request->diterima;
        $penjualan->update();

        $detail = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $item->diskon = $diskonInt;
            $item->update();

            $produk = Produk::find($item->id_produk);
            $stokKurang = $produk->stok < $item->jumlah;
            if($stokKurang && $pembayaranKurang) {
                return redirect()->route('transaksi.pembayaranDanStokKurang');
            } else if ($pembayaranKurang) {
                return redirect()->route('transaksi.pembayaranKurang');
            } else if ($stokKurang) {
                return redirect()->route('transaksi.stokKurang');
            } else {
                $produk->stok -= $item->jumlah;
                $produk->update();
            }
        }

        return redirect()->route('transaksi.selesai');
    }

    public function show($id)
    {
        $detail = PenjualanDetail::with('produk')->where('id_penjualan', $id)->get();

        return datatables()
            ->of($detail)
            ->addIndexColumn()
            ->addColumn('kode_produk', function ($detail) {
                return '<span class="label label-success">'. $detail->produk->kode_produk .'</span>';
            })
            ->addColumn('nama_produk', function ($detail) {
                return $detail->produk->nama_produk;
            })
            ->addColumn('harga_jual', function ($detail) {
                return 'Rp. '. format_uang($detail->harga_jual);
            })
            ->addColumn('jumlah', function ($detail) {
                return format_uang($detail->jumlah);
            })
            ->addColumn('diskon', function ($detail) {
                return $detail->produk->diskon . '%';
            })
            ->addColumn('subtotal', function ($detail) {
                return 'Rp. '. format_uang($detail->subtotal);
            })
            ->rawColumns(['kode_produk'])
            ->make(true);
    }

    public function destroy($id)
    {
        $penjualan = Penjualan::find($id);
        $detail    = PenjualanDetail::where('id_penjualan', $penjualan->id_penjualan)->get();
        foreach ($detail as $item) {
            $produk = Produk::find($item->id_produk);
            if ($produk) {
                $produk->stok += $item->jumlah;
                $produk->update();
            }

            $item->delete();
        }

        $penjualan->delete();

        return response(null, 204);
    }

    public function stokKurang()
    {
        $produk = Produk::orderBy('nama_produk')->get();
        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;

        // Cek apakah ada transaksi yang sedang berjalan
        $id_penjualan = session('id_penjualan');
        $penjualan = Penjualan::find($id_penjualan);
        $memberSelected = $penjualan->member ?? new Member();
        $stokKurang = true;
        $uangKurang = false;
        $id_penjualan = session('id_penjualan');
        return view('penjualan_detail.index', compact('produk', 'stokKurang', 'uangKurang', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected'));
    }

    public function pembayaranKurang()
    {
        $produk = Produk::orderBy('nama_produk')->get();
        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;

        // Cek apakah ada transaksi yang sedang berjalan
        $id_penjualan = session('id_penjualan');
        $penjualan = Penjualan::find($id_penjualan);
        $memberSelected = $penjualan->member ?? new Member();
        $stokKurang = false;
        $uangKurang = true;
        $id_penjualan = session('id_penjualan');
        return view('penjualan_detail.index', compact('produk', 'stokKurang', 'uangKurang', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected'));
    }

    public function pembayaranDanStokKurang()
    {
        $produk = Produk::orderBy('nama_produk')->get();
        $member = Member::orderBy('nama')->get();
        $diskon = Setting::first()->diskon ?? 0;

        // Cek apakah ada transaksi yang sedang berjalan
        $id_penjualan = session('id_penjualan');
        $penjualan = Penjualan::find($id_penjualan);
        $memberSelected = $penjualan->member ?? new Member();
        $stokKurang = true;
        $uangKurang = true;
        $id_penjualan = session('id_penjualan');
        return view('penjualan_detail.index', compact('produk', 'stokKurang', 'uangKurang', 'member', 'diskon', 'id_penjualan', 'penjualan', 'memberSelected'));
    }

    public function transaksiAktifBaru($id)
    {
        session(['id_penjualan' => $id]);
        return redirect()->route('transaksi.index');
    }

    public function selesai()
    {
        $setting = Setting::first();

        return view('penjualan.selesai', compact('setting'));
    }

    public function notaKecil()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();
        
        return view('penjualan.nota_kecil', compact('setting', 'penjualan', 'detail'));
    }

    public function notaBesar()
    {
        $setting = Setting::first();
        $penjualan = Penjualan::find(session('id_penjualan'));
        if (! $penjualan) {
            abort(404);
        }
        $detail = PenjualanDetail::with('produk')
            ->where('id_penjualan', session('id_penjualan'))
            ->get();

        $pdf = PDF::loadView('penjualan.nota_besar', compact('setting', 'penjualan', 'detail'));
        $pdf->setPaper(0,0,609,440, 'potrait');
        return $pdf->stream('Transaksi-'. date('Y-m-d-his') .'.pdf');
    }
}
