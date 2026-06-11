import {

  getTemplateById

} from '../database/templates.js';

// =========================
// APPLY TEMPLATE
// =========================

export function applyKnifeTemplate(
  templateId
) {

  const template =
    getTemplateById(templateId);

  if (!template) {

    console.warn(
      'Template não encontrado'
    );

    return null;

  }

  // MATERIAL

  setInputValue(
    'materialCost',
    template.materialCost
  );

  // GAS

  setInputValue(
    'gasCost',
    template.gasCost
  );

  // ENERGIA

  setInputValue(
    'energyCost',
    template.energyCost
  );

  // CONSUMIVEIS

  setInputValue(
    'consumablesCost',
    template.consumablesCost
  );

  // HORAS

  setInputValue(
    'hoursWorked',
    template.horasTrabalho
  );

  // MARGEM

  setInputValue(
    'marginPercent',
    template.margem
  );

  // DEPRECIACAO

  setInputValue(
    'depreciationCost',
    template.depreciationCost
  );

  // HORA OFICINA

  const hourlyRate =
    localStorage.getItem(
      'cutelaria_hour_cost'
    ) || 50;

  setInputValue(
    'hourlyRate',
    hourlyRate
  );

  return template;

}

// =========================
// INPUT HELPER
// =========================

function setInputValue(
  id,
  value
) {

  const input =
    document.getElementById(id);

  if (!input) {

    return;

  }

  input.value = value;

}