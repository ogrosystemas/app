export function pdfPreviewCard(
  price
) {

  return `

    <div class="
      card
      mt-6
    ">

      <div class="
        flex
        items-center
        justify-between
      ">

        <div>

          <h3 class="
            text-2xl
            font-bold
            mb-2
          ">

            PDF Premium

          </h3>

          <p class="
            text-slate-400
          ">

            Gere orçamento profissional
            para envio ao cliente.

          </p>

        </div>

        <div class="
          text-right
        ">

          <div class="
            text-slate-400
            text-sm
          ">

            Valor final

          </div>

          <div class="
            text-orange-400
            text-3xl
            font-black
          ">

            ${price}

          </div>

        </div>

      </div>

    </div>

  `;

}