  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleta de Compra</title>
    <style>
      body {
      font-family: Arial, sans-serif;
        font-size: 10px;
        width: 80mm;
        margin: 0;
      }

      .ticket {
        width: 80mm;
        margin: 0 auto;
        border: 1px solid #ffff;
      }

      .company {
        font-size: 20px;
        font-weight: bold;
      }

      .logo {
        width: 100px;
        height : 100px;
      }

      .header, .footer {
        text-align: center;
      }

      .items {
        margin-top: 10px;
      }

      .items table {
        width: 100%;
        border-collapse: collapse;
      }
      
      .items table th, .items table td {
          border:none;
          text-align: left;
          padding: 5px;
      }
      
      .totales {
        margin-top: 10px;
        text-align: right;
      }
  </style>
</head>
<body class="ticket">
 <div class="header">
    <h1 class="company">
      {{ $info->company->name }}
    </h1>
    <p>
    {{ $info->company->address }}
    </p>
    
    <p>Ruc: {{ $info->company->ruc }}</p>
    <p>
     {{ $info->company->address }}
    </p>
    <hr>
  </div>

  <div class="header">
    <h2>BOLETA DE VENTA</h2>
    <h3>{{ $info->payment->code }}</h3>
    <p>
      {{ Modules\Tenant\Modules\Treasury\Helpers\BoletaHelper::getLineStartsByLength(76,'*', ' copia boleta ')}} 
    </p>
  </div>

  <div class="detalle">
      @if($info->payment->payment_date)
      <p>
        Fecha de Emisión: {{ $info->payment->payment_date }}
      </p>
      @endif

      <p>Cliente: {{ $info->client->name }}</p>
      <p>Dirección: {{ $info->client->address }}</p>
      <p>
       {{ $info->client->document_type }} : {{ $info->client->document_number }} 
      </p>
      <p>
        Moneda de Pago: PEN
      </p>
      <p>
      <hr>
      </p>
    </div>

     <div class="items">
        <table>
            <thead>
                <tr>
                    <th>DESCRIPCIÓN</th>
                    <th style="text-align:center">CUOTA</th>
                    <th style="text-align:right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($info->detail as $item)
                <tr> 
                    <td style="text-align:left">{{ $item->description }}</td>
                    <td style="text-align:center">{{ $item->quota }}</td>
                    <td style="text-align:right"> @money($item->amount)</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="totales">
      @if ($info->is_igv_exonerated)
      <p><strong>Total:  @money($info->total_amount)  </strong></p>
      <p><strong>{{$info->total_amount_text}}</strong></p>
      @else
      <p><strong>Total Bruto:  @money($info->gross_amount)  </strong></p>
      <p><strong>IGV:  @money($info->igv_amount)  </strong></p>
      <p><strong>Total Neto:  @money($info->net_amount)  </strong></p>
      <p><strong>Total:  @money($info->total_amount)  </strong></p>
      
      <p><strong>{{$info->total_amount_text}}</strong></p>
      @endif
    </div>
    <div class="footer">
        <p>¡Gracias por su compra!</p>
        <p>Visítanos en {{ $info->latinaeduca->domain }}</p>
    </div>
</body>