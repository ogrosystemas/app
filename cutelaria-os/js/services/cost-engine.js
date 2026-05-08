function round(value) {

  return Number(
    value.toFixed(2)
  );

}

// =========================
// MATERIAL
// =========================

export function calculateMaterialCost(
  materials = []
) {

  let total = 0;

  for (const item of materials) {

    const quantity =
      Number(item.quantity || 0);

    const unitCost =
      Number(item.unitCost || 0);

    total +=
      quantity * unitCost;

  }

  return round(total);

}

// =========================
// OPERACIONAL
// =========================

export function calculateOperationalCost({

  gasCost = 0,
  charcoalCost = 0,
  energyCost = 0,
  abrasiveCost = 0,
  waterCost = 0,
  consumablesCost = 0

}) {

  const total =

    Number(gasCost)
    +
    Number(charcoalCost)
    +
    Number(energyCost)
    +
    Number(abrasiveCost)
    +
    Number(waterCost)
    +
    Number(consumablesCost);

  return round(total);

}

// =========================
// MÃO DE OBRA
// =========================

export function calculateLaborCost({

  hoursWorked = 0,
  hourlyRate = 0

}) {

  const total =

    Number(hoursWorked)
    *
    Number(hourlyRate);

  return round(total);

}

// =========================
// DEPRECIAÇÃO
// =========================

export function calculateDepreciationCost(
  equipments = []
) {

  let total = 0;

  for (const equipment of equipments) {

    const purchaseValue =
      Number(
        equipment.purchaseValue || 0
      );

    const usefulLifeMonths =
      Number(
        equipment.usefulLifeMonths || 1
      );

    const monthlyUsage =
      Number(
        equipment.monthlyUsage || 1
      );

    const knifeUsage =
      Number(
        equipment.knifeUsage || 1
      );

    const monthlyDepreciation =

      purchaseValue
      /
      usefulLifeMonths;

    const costPerUsage =

      monthlyDepreciation
      /
      monthlyUsage;

    total +=
      costPerUsage * knifeUsage;

  }

  return round(total);

}

// =========================
// MARGEM
// =========================

export function calculateProfitMargin({

  totalCost = 0,
  salePrice = 0

}) {

  if (
    Number(salePrice) <= 0
  ) {

    return 0;

  }

  const margin =

    (
      (
        Number(salePrice)
        -
        Number(totalCost)
      )
      /
      Number(salePrice)
    )
    *
    100;

  return round(margin);

}

// =========================
// LUCRO
// =========================

export function calculateNetProfit({

  totalCost = 0,
  salePrice = 0

}) {

  return round(

    Number(salePrice)
    -
    Number(totalCost)

  );

}

// =========================
// PREÇO SUGERIDO
// =========================

export function calculateSuggestedPrice({

  totalCost = 0,
  marginPercent = 100

}) {

  const multiplier =

    1
    +
    (
      Number(marginPercent)
      / 100
    );

  return round(

    Number(totalCost)
    *
    multiplier

  );

}

// =========================
// ENGINE COMPLETA
// =========================

export function calculateKnifeCost({

  materials = [],

  operational = {},

  labor = {},

  equipments = [],

  marginPercent = 100,

  customSalePrice = null

}) {

  // MATERIAL

  const materialCost =

    calculateMaterialCost(
      materials
    );

  // OPERACIONAL

  const operationalCost =

    calculateOperationalCost(
      operational
    );

  // MÃO DE OBRA

  const laborCost =

    calculateLaborCost(
      labor
    );

  // DEPRECIAÇÃO

  const depreciationCost =

    calculateDepreciationCost(
      equipments
    );

  // TOTAL

  const totalCost = round(

    materialCost
    +
    operationalCost
    +
    laborCost
    +
    depreciationCost

  );

  // PREÇO SUGERIDO

  const suggestedPrice =

    calculateSuggestedPrice({

      totalCost,

      marginPercent

    });

  // PREÇO FINAL

  const salePrice =

    customSalePrice !== null

      ? Number(customSalePrice)

      : suggestedPrice;

  // LUCRO

  const netProfit =

    calculateNetProfit({

      totalCost,
      salePrice

    });

  // MARGEM

  const margin =

    calculateProfitMargin({

      totalCost,
      salePrice

    });

  return {

    materialCost,

    operationalCost,

    laborCost,

    depreciationCost,

    totalCost,

    suggestedPrice,

    salePrice,

    netProfit,

    margin

  };

}