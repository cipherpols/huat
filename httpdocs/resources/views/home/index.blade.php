@extends('layouts.master')

@section('content')
<script>
var showPopup = function(type, hash) {
    var url = "http://infopub.sgx.com/Apps?A=COW_CorpAnnouncement_Content&B=" + type + "&F=" + hash;
    var popWindow = window.open(url,'SGX','height=700,width=700,scrollbars=1');
    popWindow.focus(); 
    return false;
}
</script>
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        HUAT Data Centre
      </h1>
      <ol class="breadcrumb">
        <li class="active"><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
    <div class="row">
      <div class="col-xs-12">

        <div class="box">
          <div class="box-header">
            <h3 class="box-title">Data was pulled from <a href="http://www.sgx.com/wps/portal/sgxweb/home/company_disclosure/company_announcements" target="_blank">SGX Portal</a></h3>
          </div>

      <div class="box-body">
        <div class="row">
        <form method="post">
          <div class="col-md-4">
            <div class="form-group">
              <label>Time Flame</label>
              <select name="time-flame" class="form-control select2" style="width: 100%;">
                {!! $timeFlame !!}
              </select>
            </div>
          </div><!--  /.col- -->

          <div class="col-md-4">
            <div class="form-group">
                <label>Data Filters</label>
                <select name="data-filter" class="form-control select2" style="width: 100%;">
                  {!! $dataFilter !!}
                </select>
              </div>
          </div><!--  /.col -->

          <div class="col-md-4">
            <div class="form-group">
              <label>Company to exclude</label>
              <select name="excluded-company[]" class="form-control select2" multiple="multiple" data-placeholder="Select a company" style="width: 100%;">
                {!! $companyList !!}
              </select>
            </div>
            <!-- /.form-group -->
          </div><!--  /.col -->

          <div class="col-md-12">
            <div class="form-group">
              <button type="submit" class="btn btn-lg btn-primary">Filter data</button>
              <button type="submit" class="btn btn-lg btn-primary">Reset filter(s)</button>
            </div>
            <!-- /.form-group -->

          </div>
          </form>
          
        </div>
        <!-- /.row -->
      </div>

          <!-- /.box-header -->
          <div class="box-body">
            <table id="sgx" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Company</th>
                <th>Security</th>
                <th>Title</th>
              </tr>
              </thead>
              <tbody>
              @foreach ($resultList as $company)
              <tr>
                <?php
                $dateTime = $company['DateTime']->toDateTime();
                $var = $dateTime->setTimezone(new DateTimeZone($timezone));;
                $date = $dateTime->format('d M Y');
                $time = $dateTime->format('g:i:s A');
                ?>
                <td>{{ $date }}</td>
                <td>{{ $time }}</td>
                <td>{{ $company['IssuerName'] }}</td>
                <td>{{ $company['SecurityName'] }}</td>
                <td><a href="javascript:;" onclick="showPopup('{{ $company['SearchTimeGroup'] }}', '{{ $company['key'] }}')">{{ $company['AnnTitle'] }}</a></td>
              </tr>
              @endforeach
              </tbody>
              <tfoot>
               <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Company</th>
                <th>Security</th>
                <th>Title</th>
              </tr>
              </tfoot>
            </table>
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
@endsection
