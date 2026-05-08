function currency(
  value
) {

  return new Intl.NumberFormat(
    'pt-BR',
    {

      style: 'currency',
      currency: 'BRL'

    }
  ).format(value);

}

// =========================
// EXPORT PDF
// =========================

export async function generatePremiumPDF(
  data
) {

  const {

    templateName,
    steelType,
    handleMaterial,
    totalCost,
    suggestedPrice,
    profit,
    margin,
    workshopName

  } = data;

  const html = `

    <html>

      <head>

        <title>

          Orçamento Premium

        </title>

        <style>

          * {

            box-sizing:
              border-box;

            margin:
              0;

            padding:
              0;

            font-family:
              Arial, sans-serif;

          }

          body {

            background:
              #07111f;

            color:
              white;

            padding:
              50px;

          }

          .header {

            display:
              flex;

            justify-content:
              space-between;

            align-items:
              center;

            margin-bottom:
              50px;

          }

          .logo {

            width:
              90px;

            height:
              90px;

            border-radius:
              28px;

            background:
              linear-gradient(
                135deg,
                #f97316,
                #ea580c
              );

            display:
              flex;

            align-items:
              center;

            justify-content:
              center;

            font-size:
              34px;

            font-weight:
              bold;

          }

          .title h1 {

            font-size:
              38px;

            margin-bottom:
              10px;

          }

          .title p {

            color:
              #94a3b8;

            font-size:
              18px;

          }

          .card {

            background:
              rgba(255,255,255,0.04);

            border:
              1px solid rgba(255,255,255,0.08);

            border-radius:
              28px;

            padding:
              30px;

            margin-bottom:
              30px;

          }

          .card h2 {

            margin-bottom:
              24px;

            font-size:
              24px;

          }

          .row {

            display:
              flex;

            justify-content:
              space-between;

            margin-bottom:
              18px;

          }

          .label {

            color:
              #94a3b8;

          }

          .highlight {

            color:
              #fb923c;

            font-weight:
              bold;

          }

          .price {

            font-size:
              42px;

            font-weight:
              bold;

            color:
              #fb923c;

            margin-top:
              10px;

          }

          .footer {

            margin-top:
              60px;

            text-align:
              center;

            color:
              #64748b;

            font-size:
              14px;

          }

          .qr {

            width:
              120px;

            height:
              120px;

            border-radius:
              20px;

            background:
              rgba(255,255,255,0.05);

            display:
              flex;

            align-items:
              center;

            justify-content:
              center;

            margin:
              0 auto;

            margin-top:
              30px;

            font-size:
              14px;

            color:
              #94a3b8;

          }

        </style>

      </head>

      <body>

        <!-- HEADER -->

        <div class="header">

          <div class="logo">

            ⚔

          </div>

          <div class="title">

            <h1>

              ${workshopName}

            </h1>

            <p>

              Orçamento Premium

            </p>

          </div>

        </div>

        <!-- FACA -->

        <div class="card">

          <h2>

            Ficha Técnica

          </h2>

          <div class="row">

            <span class="label">

              Modelo

            </span>

            <strong>

              ${templateName}

            </strong>

          </div>

          <div class="row">

            <span class="label">

              Tipo de aço

            </span>

            <strong>

              ${steelType}

            </strong>

          </div>

          <div class="row">

            <span class="label">

              Cabo

            </span>

            <strong>

              ${handleMaterial}

            </strong>

          </div>

        </div>

        <!-- FINANCEIRO -->

        <div class="card">

          <h2>

            Resumo Financeiro

          </h2>

          <div class="row">

            <span class="label">

              Custo total

            </span>

            <strong>

              ${currency(totalCost)}

            </strong>

          </div>

          <div class="row">

            <span class="label">

              Margem aplicada

            </span>

            <strong>

              ${margin}%

            </strong>

          </div>

          <div class="row">

            <span class="label">

              Lucro líquido

            </span>

            <strong>

              ${currency(profit)}

            </strong>

          </div>

          <div class="price">

            ${currency(suggestedPrice)}

          </div>

        </div>

        <!-- QR -->

        <div class="qr">

          QR CODE

        </div>

        <!-- FOOTER -->

        <div class="footer">

          Gerado automaticamente pelo Cutelaria OS

        </div>

      </body>

    </html>

  `;

  const win =
    window.open(
      '',
      '_blank'
    );

  win.document.write(
    html
  );

  win.document.close();

  setTimeout(() => {

    win.print();

  }, 500);

}