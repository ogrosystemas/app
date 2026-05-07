export async function dashboardPage() {
  return `
    <section class="dashboard-grid">

      <div class="card">
        <div class="metric-label">
          Lucro Médio
        </div>

        <div class="metric-value">
          R$ 0,00
        </div>
      </div>

      <div class="card">
        <div class="metric-label">
          Produções
        </div>

        <div class="metric-value">
          0
        </div>
      </div>

      <div class="card">
        <canvas id="costChart"></canvas>
      </div>

    </section>
  `;
}