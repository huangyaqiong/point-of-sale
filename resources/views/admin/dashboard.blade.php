@extends('layouts.master')

@section('title')
    Dashboard
@endsection

@section('breadcrumb')
    @parent
    <li class="active">Dashboard</li>
@endsection

@section('content')
    <!-- Small boxes (Stat box) -->
    <div class="row">
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>{{ $kategori }}</h3>

                    <p>Total Kategori</p>
                </div>
                <div class="icon">
                    <i class="fa fa-cube"></i>
                </div>
                <a href="{{ route('kategori.index') }}" class="small-box-footer">Lihat <i
                        class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{ $produk }}</h3>

                    <p>Total Produk</p>
                </div>
                <div class="icon">
                    <i class="fa fa-cubes"></i>
                </div>
                <a href="{{ route('produk.index') }}" class="small-box-footer">Lihat <i
                        class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>{{ $member }}</h3>

                    <p>Total Member</p>
                </div>
                <div class="icon">
                    <i class="fa fa-id-card"></i>
                </div>
                <a href="{{ route('member.index') }}" class="small-box-footer">Lihat <i
                        class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
            <!-- small box -->
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>{{ $supplier }}</h3>

                    <p>Total Supplier</p>
                </div>
                <div class="icon">
                    <i class="fa fa-truck"></i>
                </div>
                <a href="{{ route('supplier.index') }}" class="small-box-footer">Lihat <i
                        class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- ./col -->
    </div>
    <!-- /.row -->
    <!-- Main row -->
    <div class="row">
        <div class="col-lg-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Grafik Pendapatan {{ tanggal_indonesia($tanggal_awal, false) }} s/d
                        {{ tanggal_indonesia($tanggal_akhir, false) }}</h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="chart">
                                <!-- Sales Chart Canvas -->
                                <canvas id="salesChart" style="height: 250px;"></canvas>
                            </div>
                            <!-- /.chart-responsive -->
                        </div>
                    </div>
                    <!-- /.row -->
                </div>
                <div class="box-header with-border">
                    <h3 class="box-title">Penjualan Barang Tertinggi {{ tanggal_indonesia($tanggal_awal, false) }} s/d
                        {{ tanggal_indonesia($tanggal_akhir, false) }}</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="chart">
                                <!-- Sales Chart Canvas -->
                                <canvas id="barChart" style="height: 250px;"></canvas>
                            </div>
                            <!-- /.chart-responsive -->
                        </div>
                    </div>
                    <!-- /.row -->
                </div>
                <!-- /.row -->
            </div>
        </div>
        <!-- /.box -->
    </div>
    <!-- /.col -->
    </div>
    <!-- /.row (main row) -->
@endsection
{{-- {{ dd(json_encode($data_tanggal)) }} --}}
{{-- {{ dd(json_decode($penjualan['produk'])) }} --}}
@push('scripts')
    <!-- ChartJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>
    {{-- <script src="{{ asset('AdminLTE-2/bower_components/chart.js/Chart.js') }}"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script> --}}
    <script>
        $(function() {
            const barColors = ["red", "green","blue","orange","brown"];
        
            var salesChartCanvas = $('#salesChart').get(0).getContext('2d');
        
            new Chart(salesChartCanvas, {
                type: "line",
                data: {
                    labels: {{ json_encode($data_tanggal) }},
                    datasets: [{
                        data: {{ json_encode($data_pendapatan) }}
                    }]
                },
                options: {
                    legend: {display: false},
                    title: {
                        display: true,
                        text: ""
                    }
                }
            });
        
            var barChartCanvas = $('#barChart').get(0).getContext('2d');
        
            new Chart(barChartCanvas, {
                type: "bar",
                data: {
                    labels: {!!  json_encode($penjualan['produk'])  !!},
                    datasets: [{
                        backgroundColor: barColors,
                        data: {{ json_encode($penjualan['penjualan']) }}
                    }]
                },
                options: {
                    legend: {display: false},
                    title: {
                        display: true,
                        text: ""
                    }
                }
            });
        });
        </script>
@endpush