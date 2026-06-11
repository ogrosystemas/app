export const knifeTemplates = [

  // =========================
  // HUNTER
  // =========================

  {

    id:
      'hunter-classic',

    nome:
      'Hunter Classic',

    categoria:
      'Hunter',

    descricao:
      'Faca hunter artesanal clássica para campo e caça.',

    tipoAco:
      '1070',

    espessura:
      4,

    comprimentoLamina:
      14,

    materialCabo:
      'Micarta',

    bainha:
      true,

    horasTrabalho:
      6,

    margem:
      120,

    materialCost:
      180,

    gasCost:
      28,

    energyCost:
      15,

    consumablesCost:
      22,

    depreciationCost:
      18,

    suggestedPrice:
      790,

    dificuldade:
      'Média'

  },

  // =========================
  // CHEF
  // =========================

  {

    id:
      'chef-premium',

    nome:
      'Chef Premium',

    categoria:
      'Chef',

    descricao:
      'Faca chef premium de cozinha profissional.',

    tipoAco:
      '420C',

    espessura:
      3,

    comprimentoLamina:
      22,

    materialCabo:
      'Madeira estabilizada',

    bainha:
      false,

    horasTrabalho:
      8,

    margem:
      140,

    materialCost:
      260,

    gasCost:
      32,

    energyCost:
      20,

    consumablesCost:
      35,

    depreciationCost:
      25,

    suggestedPrice:
      1290,

    dificuldade:
      'Alta'

  },

  // =========================
  // BUSHCRAFT
  // =========================

  {

    id:
      'bushcraft-brutal',

    nome:
      'Bushcraft Brutal',

    categoria:
      'Bushcraft',

    descricao:
      'Bushcraft integral robusta para sobrevivência.',

    tipoAco:
      '5160',

    espessura:
      5,

    comprimentoLamina:
      16,

    materialCabo:
      'G10',

    bainha:
      true,

    horasTrabalho:
      7,

    margem:
      130,

    materialCost:
      240,

    gasCost:
      35,

    energyCost:
      18,

    consumablesCost:
      30,

    depreciationCost:
      24,

    suggestedPrice:
      1180,

    dificuldade:
      'Alta'

  },

  // =========================
  // CUTELO
  // =========================

  {

    id:
      'cutelo-industrial',

    nome:
      'Cutelo Industrial',

    categoria:
      'Cutelo',

    descricao:
      'Cutelo pesado para cozinha industrial.',

    tipoAco:
      '1075',

    espessura:
      6,

    comprimentoLamina:
      20,

    materialCabo:
      'Madeira nobre',

    bainha:
      false,

    horasTrabalho:
      9,

    margem:
      110,

    materialCost:
      320,

    gasCost:
      42,

    energyCost:
      26,

    consumablesCost:
      45,

    depreciationCost:
      32,

    suggestedPrice:
      1590,

    dificuldade:
      'Alta'

  },

  // =========================
  // DAMASCO
  // =========================

  {

    id:
      'damasco-signature',

    nome:
      'Damasco Signature',

    categoria:
      'Premium',

    descricao:
      'Faca damasco premium coleção assinatura.',

    tipoAco:
      'Damasco',

    espessura:
      4,

    comprimentoLamina:
      18,

    materialCabo:
      'Carbon Fiber',

    bainha:
      true,

    horasTrabalho:
      16,

    margem:
      220,

    materialCost:
      680,

    gasCost:
      75,

    energyCost:
      40,

    consumablesCost:
      90,

    depreciationCost:
      48,

    suggestedPrice:
      4200,

    dificuldade:
      'Extrema'

  },

  // =========================
  // TÁTICA
  // =========================

  {

    id:
      'tatica-operator',

    nome:
      'Tática Operator',

    categoria:
      'Tática',

    descricao:
      'Faca tática moderna full tang.',

    tipoAco:
      'D2',

    espessura:
      5,

    comprimentoLamina:
      17,

    materialCabo:
      'G10 Black',

    bainha:
      true,

    horasTrabalho:
      7,

    margem:
      150,

    materialCost:
      290,

    gasCost:
      38,

    energyCost:
      20,

    consumablesCost:
      34,

    depreciationCost:
      24,

    suggestedPrice:
      1490,

    dificuldade:
      'Alta'

  },

  // =========================
  // PEQUENA EDC
  // =========================

  {

    id:
      'edc-urban',

    nome:
      'EDC Urban',

    categoria:
      'EDC',

    descricao:
      'Pequena faca urbana de uso diário.',

    tipoAco:
      '440C',

    espessura:
      3,

    comprimentoLamina:
      9,

    materialCabo:
      'Micarta',

    bainha:
      true,

    horasTrabalho:
      4,

    margem:
      100,

    materialCost:
      120,

    gasCost:
      18,

    energyCost:
      10,

    consumablesCost:
      15,

    depreciationCost:
      12,

    suggestedPrice:
      490,

    dificuldade:
      'Baixa'

  }

];

// =========================
// HELPERS
// =========================

export function getTemplateById(
  id
) {

  return knifeTemplates.find(
    (template) => template.id === id
  );

}

export function getTemplatesByCategory(
  categoria
) {

  return knifeTemplates.filter(
    (template) =>

      template.categoria === categoria
  );

}

export function getFeaturedTemplates() {

  return [

    knifeTemplates[0],

    knifeTemplates[1],

    knifeTemplates[4]

  ];

}