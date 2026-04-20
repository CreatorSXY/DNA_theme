(function () {
  const config = window.dnaB2BConfig || null;
  const wizard = document.getElementById('dnaB2BWizard');

  if (!config || !wizard) {
    return;
  }

  const bodyEl = document.body;
  const body = document.getElementById('dnaB2BWizardBody');
  const title = document.getElementById('dnaB2BWizardTitle');
  const progress = document.getElementById('dnaB2BWizardProgress');
  const errorBox = document.getElementById('dnaB2BWizardError');
  const backButton = document.getElementById('dnaB2BWizardBack');
  const nextButton = document.getElementById('dnaB2BWizardNext');
  const closeButtons = wizard.querySelectorAll('[data-dna-b2b-close]');
  const openButtons = document.querySelectorAll('[data-dna-b2b-open]');
  const floatingButton = document.querySelector('[data-dna-b2b-floating]');
  const heroActions = document.getElementById('dna-b2b-hero-actions');
  const focusableSelector = 'button, [href], input, textarea, select, [tabindex]:not([tabindex="-1"])';
  const money = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  const defaultCatalogItems = [
    { id: 't_shirts', label: 'T-shirts', unitCost: 4, unitWeight: 0.25, enabled: 1 },
    { id: 'hoodies', label: 'Hoodies', unitCost: 8, unitWeight: 0.55, enabled: 1 },
    { id: 'caps', label: 'Caps', unitCost: 3.5, unitWeight: 0.18, enabled: 1 },
    { id: 'decor_magnets', label: 'Decor magnets', unitCost: 1.2, unitWeight: 0.08, enabled: 1 },
    { id: 'mugs', label: 'Mugs', unitCost: 2.8, unitWeight: 0.42, enabled: 1 },
    { id: 'canvas_bags', label: 'Canvas bags', unitCost: 2.3, unitWeight: 0.2, enabled: 1 },
    { id: 'keychains', label: 'Keychains', unitCost: 1.4, unitWeight: 0.05, enabled: 1 },
    { id: 'paper_bags', label: 'Paper bags', unitCost: 0.6, unitWeight: 0.06, enabled: 1 },
  ];

  const defaultShippingModes = [
    { id: 'sea', label: 'Sea', rate: 1.2, days: 30, enabled: 1 },
    { id: 'air', label: 'Air', rate: 3.8, days: 10, enabled: 1 },
  ];

  const defaultLogoDesignTiers = [
    { id: 'logo_execution', label: 'Execution Layer', details: 'Includes up to 5 revision rounds. Additional revisions are billed at the same hourly rate. Work follows predefined direction. Strategic repositioning is not included.', cost: 30, days: 2, enabled: 1, source: 'logo' },
    { id: 'logo_system', label: 'System Layer', details: 'Includes 8-10 revision rounds depending on project scope. Adjustments within the defined system are supported. Direction resets outside approved scope are quoted separately.', cost: 50, days: 4, enabled: 1, source: 'logo' },
    { id: 'logo_strategic', label: 'Strategic Layer', details: 'Iterative refinement without fixed revision limits (within approved scope). Direction evolves collaboratively until alignment is achieved.', cost: 80, days: 6, enabled: 1, source: 'logo' },
  ];

  const defaultMerchDesignTiers = [
    { id: 'merch_execution', label: 'Execution Layer', details: 'Includes up to 5 revision rounds. Additional revisions are billed at the same hourly rate. Work follows predefined direction. Strategic repositioning is not included.', cost: 30, days: 2, enabled: 1, source: 'merch' },
    { id: 'merch_system', label: 'System Layer', details: 'Includes 8-10 revision rounds depending on project scope. Adjustments within the defined system are supported. Direction resets outside approved scope are quoted separately.', cost: 50, days: 3, enabled: 1, source: 'merch' },
    { id: 'merch_strategic', label: 'Strategic Layer', details: 'Iterative refinement without fixed revision limits (within approved scope). Direction evolves collaboratively until alignment is achieved.', cost: 80, days: 4, enabled: 1, source: 'merch' },
  ];

  const noDesignTier = {
    id: 'none',
    label: 'No Design Service',
    details: 'No design service included in this path.',
    cost: 0,
    days: 0,
    enabled: 1,
    source: 'none',
  };

  function toNumber(value, fallback) {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function toPositiveNumber(value, fallback, precision) {
    let next = toNumber(value, fallback);
    if (next < 0) next = 0;
    const p = typeof precision === 'number' ? precision : 2;
    return Number(next.toFixed(p));
  }

  function sanitizeKey(value) {
    return String(value || '').toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/_+/g, '_').replace(/^_+|_+$/g, '');
  }

  function normalizeCatalogItems(items) {
    const source = Array.isArray(items) && items.length ? items : defaultCatalogItems;
    const seen = {};
    const normalized = [];

    source.forEach(function (item) {
      if (!item) return;
      const id = sanitizeKey(item.id);
      if (!id || seen[id]) return;
      seen[id] = true;
      const enabled = item.enabled === undefined ? 1 : (item.enabled ? 1 : 0);
      const label = String(item.label || id).trim();
      normalized.push({
        id: id,
        label: label || id,
        unitCost: toPositiveNumber(item.unitCost, toPositiveNumber(item.unit_cost, 0, 2), 2),
        unitWeight: toPositiveNumber(item.unitWeight, toPositiveNumber(item.unit_weight, 0, 3), 3),
        enabled: enabled,
      });
    });

    return normalized.filter(function (item) {
      return item.enabled;
    });
  }

  function normalizeShippingModes(modes) {
    const source = Array.isArray(modes) && modes.length ? modes : defaultShippingModes;
    const seen = {};
    const normalized = [];

    source.forEach(function (mode) {
      if (!mode) return;
      const id = sanitizeKey(mode.id);
      if (!id || seen[id]) return;
      seen[id] = true;
      const enabled = mode.enabled === undefined ? 1 : (mode.enabled ? 1 : 0);
      const label = String(mode.label || id).trim();
      normalized.push({
        id: id,
        label: label || id,
        rate: toPositiveNumber(mode.rate, toPositiveNumber(mode.rate_per_kg, 0, 2), 2),
        days: Math.max(0, Math.round(toNumber(mode.days, mode.lead_days || 0))),
        enabled: enabled,
      });
    });

    return normalized.filter(function (mode) {
      return mode.enabled;
    });
  }

  function normalizeDesignTiers(tiers, fallback, source) {
    const sourceRows = Array.isArray(tiers) && tiers.length ? tiers : fallback;
    const normalized = [];
    const seen = {};

    sourceRows.forEach(function (tier) {
      if (!tier) return;
      const id = sanitizeKey(tier.id);
      if (!id || seen[id]) return;
      seen[id] = true;

      const enabled = tier.enabled === undefined ? 1 : (tier.enabled ? 1 : 0);
      const label = String(tier.label || id).trim();
      const details = String(tier.details || '').trim();
      normalized.push({
        id: id,
        label: label || id,
        details: details,
        cost: toPositiveNumber(tier.cost, toPositiveNumber(tier.hourlyRate, toPositiveNumber(tier.hourly_rate, 0, 2), 2), 2),
        days: Math.max(0, Math.round(toNumber(tier.days, tier.leadDays || tier.lead_days || 0))),
        enabled: enabled,
        source: source,
      });
    });

    return normalized.filter(function (tier) {
      return tier.enabled;
    });
  }

  function normalizePromoCode(value) {
    return String(value || '').trim().toUpperCase().replace(/[^A-Z0-9_-]/g, '');
  }

  function normalizePromoCodes(rows) {
    const sourceRows = Array.isArray(rows) ? rows : [];
    const normalized = [];
    const seen = {};

    sourceRows.forEach(function (row) {
      if (!row) return;
      const code = normalizePromoCode(row.code);
      if (!code || seen[code]) return;
      seen[code] = true;
      const enabled = row.enabled === undefined ? 1 : (row.enabled ? 1 : 0);
      const discountType = String(row.discountType || row.discount_type || 'percent').toLowerCase();
      const type = (discountType === 'fixed' || discountType === 'percent') ? discountType : 'percent';
      let value = toPositiveNumber(row.discountValue, toPositiveNumber(row.discount_value, 0, 2), 2);
      if (type === 'percent') {
        value = Math.min(100, Math.max(0, value));
      }

      normalized.push({
        code: code,
        discountType: type,
        discountValue: value,
        enabled: enabled,
      });
    });

    return normalized.filter(function (promo) {
      return promo.enabled;
    });
  }

  const catalogItems = normalizeCatalogItems(config.catalogItems);
  const shippingModes = normalizeShippingModes(config.shippingModes);
  const logoDesignTiers = normalizeDesignTiers(config.logoDesignTiers, defaultLogoDesignTiers, 'logo');
  const merchDesignTiers = normalizeDesignTiers(config.merchDesignTiers, defaultMerchDesignTiers, 'merch');
  const promoCodes = normalizePromoCodes(config.promoCodes);

  const catalogMap = {};
  catalogItems.forEach(function (item) {
    catalogMap[item.id] = item;
  });

  const shippingMap = {};
  shippingModes.forEach(function (mode) {
    shippingMap[mode.id] = mode;
  });

  const promoMap = {};
  promoCodes.forEach(function (promo) {
    promoMap[promo.code] = promo;
  });

  const merchOptions = Object.assign({}, config.merchOptions || {});
  if (!Object.keys(merchOptions).length) {
    catalogItems.forEach(function (item) {
      merchOptions[item.id] = item.label;
    });
  }
  if (!merchOptions.other) {
    merchOptions.other = 'Other merch';
  }

  function createDefaultEstimate() {
    const firstShipping = shippingModes[0] || null;
    const firstLogoTier = logoDesignTiers[0] || noDesignTier;
    return {
      designOption: firstLogoTier.id,
      productionDays: 12,
      shippingId: firstShipping ? firstShipping.id : '',
      items: [],
      shipping: firstShipping ? {
        id: firstShipping.id,
        label: firstShipping.label,
        rate: firstShipping.rate,
        days: firstShipping.days,
      } : {
        id: '',
        label: '',
        rate: 0,
        days: 0,
      },
      designCost: toPositiveNumber(toPositiveNumber(firstLogoTier.cost, 0, 2) * 9, 0, 2),
      designDays: Math.max(0, Math.round(firstLogoTier.days || 0)),
      production: 0,
      logistics: 0,
      margin: 0,
      discount: 0,
      total: 0,
      totalDays: 0,
      quantity: 0,
      unitCost: 0,
      unitWeight: 0,
      shippingMode: firstShipping ? firstShipping.id : '',
      shippingRate: firstShipping ? firstShipping.rate : 0,
      shippingDays: firstShipping ? firstShipping.days : 0,
      designPackage: {
        id: firstLogoTier.id,
        label: firstLogoTier.label,
        details: firstLogoTier.details || '',
        cost: toPositiveNumber(firstLogoTier.cost, 0, 2),
        days: Math.max(0, Math.round(firstLogoTier.days || 0)),
        hourlyRate: toPositiveNumber(firstLogoTier.cost, 0, 2),
        effectiveHours: 9,
        source: firstLogoTier.source || 'logo',
      },
      designHourlyRate: toPositiveNumber(firstLogoTier.cost, 0, 2),
      designEffectiveHours: 9,
      promo: {
        code: '',
        type: '',
        value: 0,
        discountAmount: 0,
      },
    };
  }

  function createDefaultState() {
    return {
      requestType: '',
      logoMode: '',
      logoIdeas: '',
      logoOverview: '',
      merchService: '',
      merchItems: [],
      customMerchText: '',
      quantities: {},
      logoFiles: [],
      merchFiles: [],
      name: '',
      email: '',
      brandName: '',
      timeline: '',
      notes: '',
      honeypot: '',
      promoInput: '',
      promoFeedback: '',
      promoFeedbackType: '',
      estimate: createDefaultEstimate(),
      submitted: false,
    };
  }

  let state = createDefaultState();
  let currentStepIndex = 0;
  let isOpen = false;
  let isSubmitting = false;
  let lastFocused = null;
  let submittedEstimate = null;

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function humanFileSize(bytes) {
    const mb = bytes / (1024 * 1024);
    return mb >= 1 ? mb.toFixed(mb >= 10 ? 0 : 1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  function uniqueList(values) {
    return Array.from(new Set(values));
  }

  function requiresMerchQuantities() {
    return state.merchService === 'production_only' || state.merchService === 'design_and_production';
  }

  function isMerchProductionPath() {
    return state.requestType === 'merchandise' && state.merchService !== 'design_only';
  }

  function activeFiles() {
    return state.requestType === 'logo' ? state.logoFiles : state.merchFiles;
  }

  function fileIdentity(file) {
    return [file.name || '', file.size || 0, file.lastModified || 0].join('::');
  }

  function mergeSelectedFiles(existingFiles, nextFiles) {
    const merged = [];
    const seen = {};

    existingFiles.concat(nextFiles).forEach(function (file) {
      const key = fileIdentity(file);
      if (seen[key]) {
        return;
      }
      seen[key] = true;
      merged.push(file);
    });

    if (merged.length > config.maxFiles) {
      setError('You can upload up to ' + config.maxFiles + ' files.');
      return merged.slice(0, config.maxFiles);
    }

    setError('');
    return merged;
  }

  function cleanupState() {
    const allowedMerch = Object.keys(merchOptions);
    state.merchItems = uniqueList(state.merchItems).filter(function (item) {
      return allowedMerch.indexOf(item) !== -1;
    });

    if (state.merchItems.indexOf('other') === -1) {
      state.customMerchText = '';
      delete state.quantities.other;
    }

    Object.keys(state.quantities).forEach(function (slug) {
      if (state.merchItems.indexOf(slug) === -1) {
        delete state.quantities[slug];
      }
    });

    if (!requiresMerchQuantities()) {
      state.quantities = {};
    }
  }

  function getDesignTierSource() {
    if (state.requestType === 'logo') {
      return 'logo';
    }

    if (state.requestType === 'merchandise') {
      if (state.merchService === 'design_and_production' || state.merchService === 'design_only') {
        return 'merch';
      }
      return 'none';
    }

    return 'none';
  }

  function getActiveDesignTiers() {
    const source = getDesignTierSource();
    if (source === 'logo') {
      return logoDesignTiers.length ? logoDesignTiers : [noDesignTier];
    }
    if (source === 'merch') {
      return merchDesignTiers.length ? merchDesignTiers : [noDesignTier];
    }
    return [noDesignTier];
  }

  function getEstimateDesignOptions() {
    return getActiveDesignTiers().map(function (tier) {
      return tier.id;
    });
  }

  function getTierLevel(tierId, tiers) {
    if (!Array.isArray(tiers) || !tiers.length) {
      return 0;
    }
    const index = tiers.findIndex(function (tier) {
      return tier.id === tierId;
    });
    if (index < 0) {
      return 0;
    }
    if (tiers.length === 1) {
      return 0.5;
    }
    return index / (tiers.length - 1);
  }

  function computeDesignEffectiveHours(source, selectedTier, tiers, selectedItemCount) {
    const level = getTierLevel(selectedTier.id, tiers);

    if (source === 'logo') {
      return toPositiveNumber(6 + (6 * level), 6, 1);
    }

    if (source === 'merch') {
      const perItemHours = 1 + (2 * level);
      const itemCount = Math.max(1, Math.round(toNumber(selectedItemCount, 1)));
      return toPositiveNumber(perItemHours * itemCount, perItemHours, 1);
    }

    return 0;
  }

  function formatHours(value) {
    const rounded = toPositiveNumber(value, 0, 1);
    return rounded % 1 === 0 ? String(Math.round(rounded)) : rounded.toFixed(1);
  }

  function getShippingById(id) {
    if (id && shippingMap[id]) {
      return shippingMap[id];
    }
    return shippingModes[0] || null;
  }

  function getPromoByCode(code) {
    const normalized = normalizePromoCode(code);
    if (!normalized) return null;
    return promoMap[normalized] || null;
  }

  function buildEstimateItems(isProductionPath) {
    if (state.requestType !== 'merchandise') {
      return [];
    }

    return state.merchItems.map(function (slug) {
      const catalogItem = catalogMap[slug] || null;
      const baseLabel = merchOptions[slug] || slug;
      const label = slug === 'other' && state.customMerchText ? state.customMerchText : baseLabel;
      const qtyParsed = parseInt(state.quantities[slug], 10);
      const qty = Number.isFinite(qtyParsed) && qtyParsed > 0 ? qtyParsed : 0;
      const unitCost = catalogItem ? catalogItem.unitCost : 0;
      const unitWeight = catalogItem ? catalogItem.unitWeight : 0;
      const autoPriced = Boolean(isProductionPath && catalogItem && slug !== 'other');
      const lineProduction = autoPriced ? toPositiveNumber(qty * unitCost, 0, 2) : 0;
      const lineWeight = autoPriced ? toPositiveNumber(qty * unitWeight, 0, 3) : 0;

      return {
        id: slug,
        label: label,
        qty: qty,
        unitCost: unitCost,
        unitWeight: unitWeight,
        lineProduction: lineProduction,
        lineWeight: lineWeight,
        autoPriced: autoPriced,
      };
    });
  }

  function applyEstimateProfile() {
    const estimate = state.estimate || createDefaultEstimate();
    const options = getEstimateDesignOptions();

    if (options.indexOf(estimate.designOption) === -1) {
      estimate.designOption = options[0];
    }

    if (state.requestType === 'logo') {
      estimate.productionDays = 0;
    }

    if (state.requestType === 'merchandise') {
      if (state.merchService === 'design_only') {
        estimate.productionDays = 0;
      }
      if (state.merchService === 'production_only') {
        estimate.designOption = 'none';
      }
      if (isMerchProductionPath() && (!estimate.productionDays || estimate.productionDays < 1)) {
        estimate.productionDays = 12;
      }
    }

    if (!estimate.shippingId || !getShippingById(estimate.shippingId)) {
      const firstShipping = shippingModes[0] || null;
      estimate.shippingId = firstShipping ? firstShipping.id : '';
    }

    if (!estimate.promo || typeof estimate.promo !== 'object') {
      estimate.promo = { code: '', type: '', value: 0, discountAmount: 0 };
    }

    state.estimate = estimate;
    recalculateEstimate();
  }

  function recalculateEstimate() {
    const estimate = state.estimate || createDefaultEstimate();
    const designSource = getDesignTierSource();
    const designTiers = getActiveDesignTiers();
    const fallbackTier = designTiers[0] || noDesignTier;
    const selectedTier = designTiers.find(function (tier) {
      return tier.id === estimate.designOption;
    }) || fallbackTier;

    const designOption = selectedTier.id;
    const designHourlyRate = toPositiveNumber(selectedTier.cost, 0, 2);
    const selectedItemCount = state.requestType === 'merchandise'
      ? state.merchItems.filter(function (slug) { return slug !== 'other'; }).length
      : 0;
    const designEffectiveHours = computeDesignEffectiveHours(designSource, selectedTier, designTiers, selectedItemCount);
    const designCost = toPositiveNumber(designHourlyRate * designEffectiveHours, 0, 2);
    const designDays = Math.max(0, Math.round(selectedTier.days || 0));
    const designPackage = {
      id: selectedTier.id,
      label: selectedTier.label || selectedTier.id,
      details: selectedTier.details || '',
      cost: designHourlyRate,
      days: designDays,
      hourlyRate: designHourlyRate,
      effectiveHours: designEffectiveHours,
      source: selectedTier.source || designSource || 'none',
    };

    const isProductionPath = isMerchProductionPath();
    const items = buildEstimateItems(isProductionPath);
    const autoItems = isProductionPath ? items.filter(function (item) {
      return item.autoPriced;
    }) : [];

    const productionDays = isProductionPath ? Math.max(0, Math.round(toNumber(estimate.productionDays, 0))) : 0;
    let production = 0;
    let totalWeight = 0;
    let quantityTotal = 0;

    autoItems.forEach(function (item) {
      production += item.lineProduction;
      totalWeight += item.lineWeight;
      quantityTotal += item.qty;
    });

    production = toPositiveNumber(production, 0, 2);
    totalWeight = toPositiveNumber(totalWeight, 0, 3);

    const shippingMode = isProductionPath && autoItems.length ? getShippingById(estimate.shippingId) : null;
    const shippingRate = shippingMode ? toPositiveNumber(shippingMode.rate, 0, 2) : 0;
    const shippingDays = shippingMode ? Math.max(0, Math.round(shippingMode.days)) : 0;
    const logistics = isProductionPath && autoItems.length ? toPositiveNumber(totalWeight * shippingRate, 0, 2) : 0;

    const margin = toPositiveNumber((designCost + production) * 0.25, 0, 2);
    const preLogistics = toPositiveNumber(designCost + production + margin, 0, 2);

    let promoCode = normalizePromoCode(estimate.promo && estimate.promo.code ? estimate.promo.code : '');
    let promoType = '';
    let promoValue = 0;
    let discountAmount = 0;
    if (promoCode) {
      const promo = getPromoByCode(promoCode);
      if (promo) {
        promoType = promo.discountType;
        promoValue = promo.discountValue;
        const rawDiscount = promoType === 'percent'
          ? preLogistics * (promoValue / 100)
          : promoValue;
        discountAmount = toPositiveNumber(Math.min(preLogistics, Math.max(0, rawDiscount)), 0, 2);
      } else {
        promoCode = '';
      }
    }

    const total = toPositiveNumber(preLogistics - discountAmount + logistics, 0, 2);
    const totalDays = Math.max(0, Math.round(designDays + productionDays + shippingDays));

    state.estimate = {
      designOption: designOption,
      productionDays: productionDays,
      shippingId: shippingMode ? shippingMode.id : '',
      items: items,
      shipping: shippingMode ? {
        id: shippingMode.id,
        label: shippingMode.label,
        rate: shippingRate,
        days: shippingDays,
      } : {
        id: '',
        label: '',
        rate: 0,
        days: 0,
      },
      designCost: designCost,
      designHourlyRate: designHourlyRate,
      designEffectiveHours: designEffectiveHours,
      designDays: designDays,
      production: production,
      logistics: logistics,
      margin: margin,
      discount: discountAmount,
      total: total,
      totalDays: totalDays,
      quantity: quantityTotal,
      unitCost: 0,
      unitWeight: totalWeight,
      shippingMode: shippingMode ? shippingMode.id : '',
      shippingRate: shippingRate,
      shippingDays: shippingDays,
      designPackage: designPackage,
      promo: {
        code: promoCode,
        type: promoType,
        value: promoValue,
        discountAmount: discountAmount,
      },
    };
  }

  function serializeEstimate(estimate) {
    const safe = estimate || {};
    const items = Array.isArray(safe.items) ? safe.items : [];
    const shipping = safe.shipping || {};
    const designPackage = safe.designPackage || {};
    const promo = safe.promo || {};

    return {
      design_option: safe.designOption || '',
      shipping_mode: safe.shippingMode || safe.shippingId || '',
      design_cost: safe.designCost || 0,
      design_hourly_rate: safe.designHourlyRate || (designPackage.hourlyRate || designPackage.cost || 0),
      design_effective_hours: safe.designEffectiveHours || (designPackage.effectiveHours || 0),
      design_days: safe.designDays || 0,
      quantity: safe.quantity || 0,
      unit_cost: safe.unitCost || 0,
      unit_weight: safe.unitWeight || 0,
      production_days: safe.productionDays || 0,
      shipping_rate: safe.shippingRate || shipping.rate || 0,
      shipping_days: safe.shippingDays || shipping.days || 0,
      production: safe.production || 0,
      logistics: safe.logistics || 0,
      margin: safe.margin || 0,
      discount: safe.discount || promo.discountAmount || 0,
      total: safe.total || 0,
      total_days: safe.totalDays || 0,
      items: items.map(function (item) {
        return {
          id: item.id || '',
          label: item.label || '',
          qty: item.qty || 0,
          unit_cost: item.unitCost || 0,
          unit_weight: item.unitWeight || 0,
          line_production: item.lineProduction || 0,
          line_weight: item.lineWeight || 0,
          auto_priced: item.autoPriced ? 1 : 0,
        };
      }),
      shipping: {
        id: shipping.id || '',
        label: shipping.label || '',
        rate: shipping.rate || 0,
        days: shipping.days || 0,
      },
      design_package: {
        id: designPackage.id || '',
        label: designPackage.label || '',
        details: designPackage.details || '',
        cost: designPackage.cost || 0,
        days: designPackage.days || 0,
        hourly_rate: designPackage.hourlyRate || designPackage.cost || 0,
        effective_hours: designPackage.effectiveHours || 0,
        source: designPackage.source || '',
      },
      promo: {
        code: promo.code || '',
        type: promo.type || '',
        value: promo.value || 0,
        discount_amount: promo.discountAmount || 0,
      },
    };
  }

  function getSteps() {
    const steps = ['request_type'];

    if (state.requestType === 'logo') {
      steps.push('logo_mode', 'logo_details', 'estimate', 'contact');
    }

    if (state.requestType === 'merchandise') {
      steps.push('merch_service', 'merch_items');

      if (requiresMerchQuantities()) {
        steps.push('merch_quantities');
      }

      steps.push('merch_uploads', 'estimate', 'contact');
    }

    return steps;
  }

  function getCurrentStep() {
    const steps = getSteps();
    if (currentStepIndex > steps.length - 1) {
      currentStepIndex = Math.max(0, steps.length - 1);
    }
    return steps[currentStepIndex] || 'request_type';
  }

  function getStepMeta(stepId) {
    switch (stepId) {
      case 'request_type':
        return {
          title: 'Start Your Project',
          nextLabel: 'Next',
        };
      case 'logo_mode':
        return {
          title: 'Logo Direction',
          nextLabel: 'Next',
        };
      case 'logo_details':
        return {
          title: state.logoMode === 'has_sketch' ? 'Share Your Sketch' : 'Describe Your Logo',
          nextLabel: 'Next',
        };
      case 'merch_service':
        return {
          title: 'Merchandise Scope',
          nextLabel: 'Next',
        };
      case 'merch_items':
        return {
          title: 'Select Merchandise',
          nextLabel: 'Next',
        };
      case 'merch_quantities':
        return {
          title: 'Project Quantities',
          nextLabel: 'Next',
        };
      case 'merch_uploads':
        return {
          title: 'Project Files',
          nextLabel: 'Next',
        };
      case 'estimate':
        return {
          title: 'Rough Estimate',
          nextLabel: 'Next',
        };
      case 'contact':
        return {
          title: 'Project Contact',
          nextLabel: 'Submit Request',
        };
      default:
        return {
          title: 'Start Your Project',
          nextLabel: 'Next',
        };
    }
  }

  function shouldAutoAdvanceStep(stepId) {
    return stepId === 'request_type' || stepId === 'logo_mode' || stepId === 'merch_service';
  }

  function autoAdvanceFromSelection(stepId) {
    if (!shouldAutoAdvanceStep(stepId) || isSubmitting || state.submitted) {
      render();
      return;
    }

    if (getCurrentStep() !== stepId) {
      render();
      return;
    }

    if (!validateCurrentStep()) {
      return;
    }

    currentStepIndex = Math.min(currentStepIndex + 1, getSteps().length - 1);
    render();
  }

  function renderSingleOption(name, value, label, meta, selected) {
    const checked = selected ? ' checked' : '';
    const stateClass = selected ? ' is-selected' : '';

    return (
      '<label class="dna-b2b-option' + stateClass + '">' +
        '<input type="radio" name="' + escapeHtml(name) + '" value="' + escapeHtml(value) + '"' + checked + '>' +
        '<span class="dna-b2b-option__label">' + escapeHtml(label) + '</span>' +
        '<span class="dna-b2b-option__meta">' + escapeHtml(meta) + '</span>' +
      '</label>'
    );
  }

  function renderMerchOption(slug, label, selected) {
    const checked = selected ? ' checked' : '';
    const stateClass = selected ? ' is-selected' : '';

    return (
      '<label class="dna-b2b-option' + stateClass + '">' +
        '<input type="checkbox" name="merch_items[]" value="' + escapeHtml(slug) + '"' + checked + '>' +
        '<span class="dna-b2b-option__label">' + escapeHtml(label) + '</span>' +
      '</label>'
    );
  }

  function renderFileList(files) {
    if (!files || !files.length) {
      return '';
    }

    return (
      '<div class="dna-b2b-file-list">' +
        files.map(function (file) {
          return '<div class="dna-b2b-file-list__item">' + escapeHtml(file.name) + ' · ' + escapeHtml(humanFileSize(file.size || 0)) + '</div>';
        }).join('') +
      '</div>'
    );
  }

  function selectedMerchSummary() {
    return state.merchItems.map(function (slug) {
      let label = merchOptions[slug] || slug;

      if (slug === 'other' && state.customMerchText) {
        label = state.customMerchText;
      }

      if (requiresMerchQuantities() && state.quantities[slug]) {
        label += ' × ' + state.quantities[slug];
      }

      return label;
    });
  }

  function renderSummaryRows() {
    const rows = [];

    rows.push({
      term: 'Request',
      value: state.requestType === 'logo' ? 'Logo design' : 'Merchandise',
    });

    if (state.requestType === 'logo') {
      rows.push({
        term: 'Approach',
        value: state.logoMode === 'has_sketch' ? 'Preliminary sketch provided' : 'Start from scratch',
      });
    }

    if (state.requestType === 'merchandise') {
      const serviceLabels = {
        production_only: 'Production Only',
        design_and_production: 'Design + Production',
        design_only: 'Design Only',
      };

      rows.push({
        term: 'Service',
        value: serviceLabels[state.merchService] || 'Merchandise',
      });

      rows.push({
        term: 'Items',
        value: selectedMerchSummary().join(', '),
      });
    }

    rows.push({
      term: 'Files',
      value: activeFiles().length ? activeFiles().length + ' attached' : 'No files attached',
    });

    rows.push({
      term: 'Estimate',
      value: money.format((state.estimate && state.estimate.total) ? state.estimate.total : 0),
    });

    if (state.estimate && state.estimate.promo && state.estimate.promo.code) {
      rows.push({
        term: 'Promo',
        value: state.estimate.promo.code,
      });
    }

    return (
      '<div class="dna-b2b-summary">' +
        rows.map(function (row) {
          return (
            '<div class="dna-b2b-summary__row">' +
              '<div class="dna-b2b-summary__term">' + escapeHtml(row.term) + '</div>' +
              '<div class="dna-b2b-summary__value">' + escapeHtml(row.value) + '</div>' +
            '</div>'
          );
        }).join('') +
      '</div>'
    );
  }

  function renderRequestTypeStep() {
    return (
      '<section class="dna-b2b-step">' +
        '<p class="dna-b2b-step__kicker">B2B Guided Intake</p>' +
        '<h3 class="dna-b2b-step__title">What would you like us to help with?</h3>' +
        '<p class="dna-b2b-step__copy">Choose the path that best matches your current project. We will adapt the next steps to what you need.</p>' +
        '<div class="dna-b2b-options">' +
          renderSingleOption('request_type', 'logo', 'I want to design a logo', 'Share your direction, upload a sketch if you have one, and let us turn it into a refined identity.', state.requestType === 'logo') +
          renderSingleOption('request_type', 'merchandise', 'I want to create merchandise for my brand', 'Choose your merchandise needs, select items, and tell us what should be designed or produced.', state.requestType === 'merchandise') +
        '</div>' +
      '</section>'
    );
  }

  function renderLogoModeStep() {
    return (
      '<section class="dna-b2b-step">' +
        '<p class="dna-b2b-step__kicker">Logo Design</p>' +
        '<h3 class="dna-b2b-step__title">How would you like to begin?</h3>' +
        '<p class="dna-b2b-step__copy">Choose whether you already have a sketch or would like us to start from your business direction alone.</p>' +
        '<div class="dna-b2b-options">' +
          renderSingleOption('logo_mode', 'has_sketch', 'I have the preliminary sketch/drawing', 'Upload your draft and tell us what you want the final logo to communicate.', state.logoMode === 'has_sketch') +
          renderSingleOption('logo_mode', 'from_scratch', 'I do not have the preliminary sketch/drawing (start from scratch)', 'Tell us about your business, direction, and the feeling you want the logo to carry.', state.logoMode === 'from_scratch') +
        '</div>' +
      '</section>'
    );
  }

  function renderLogoDetailsStep() {
    const isSketch = state.logoMode === 'has_sketch';
    const bodyCopy = isSketch
      ? 'Upload your sketch or reference files and tell us the overall idea for the logo.'
      : 'Write down your business overview and the direction you want us to explore for the logo.';
    const fieldHtml = isSketch
      ? (
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-logo-upload">Upload sketch or references</label>' +
          '<input id="dna-b2b-logo-upload" type="file" name="logo_uploads" accept="' + escapeHtml(config.accept) + '" multiple>' +
          '<div class="dna-b2b-field__hint">Accepted files: PDF, JPG, PNG, WEBP, ZIP. You can add files in multiple selections. Up to ' + escapeHtml(String(config.maxFiles)) + ' files, ' + escapeHtml(humanFileSize(config.maxFileSize)) + ' each.</div>' +
          renderFileList(state.logoFiles) +
        '</div>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-logo-ideas">Your ideas for the logo</label>' +
          '<textarea id="dna-b2b-logo-ideas" name="logo_ideas" placeholder="Tell us about the style, references, symbols, audience, and mood you want the final logo to carry.">' + escapeHtml(state.logoIdeas) + '</textarea>' +
        '</div>'
      )
      : (
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-logo-overview">Business overview and logo direction</label>' +
          '<textarea id="dna-b2b-logo-overview" name="logo_overview" placeholder="Tell us what your business does, who it serves, and how you want the logo to feel.">' + escapeHtml(state.logoOverview) + '</textarea>' +
        '</div>'
      );

    return (
      '<section class="dna-b2b-step dna-b2b-step--narrow">' +
        '<p class="dna-b2b-step__kicker">Logo Details</p>' +
        '<h3 class="dna-b2b-step__title">' + escapeHtml(isSketch ? 'Share your sketch' : 'Describe your business') + '</h3>' +
        '<p class="dna-b2b-step__copy">' + escapeHtml(bodyCopy) + '</p>' +
        fieldHtml +
      '</section>'
    );
  }

  function renderMerchServiceStep() {
    return (
      '<section class="dna-b2b-step">' +
        '<p class="dna-b2b-step__kicker">Merchandise</p>' +
        '<h3 class="dna-b2b-step__title">What part of the merchandise process do you need?</h3>' +
        '<p class="dna-b2b-step__copy">Select the level of support that matches your project today.</p>' +
        '<div class="dna-b2b-options">' +
          renderSingleOption('merch_service', 'design_and_production', 'Design + Production', 'We shape the merchandise direction with you, then help move it into production.', state.merchService === 'design_and_production') +
          renderSingleOption('merch_service', 'design_only', 'Design Only', 'We prepare the design system and production-ready files without handling manufacturing.', state.merchService === 'design_only') +
          renderSingleOption('merch_service', 'production_only', 'Production Only', 'We use your existing artwork and prepare the production path, quantities, and next steps.', state.merchService === 'production_only') +
        '</div>' +
      '</section>'
    );
  }

  function renderMerchItemsStep() {
    const merchCards = Object.keys(merchOptions).map(function (slug) {
      return renderMerchOption(slug, merchOptions[slug], state.merchItems.indexOf(slug) !== -1);
    }).join('');

    return (
      '<section class="dna-b2b-step">' +
        '<p class="dna-b2b-step__kicker">Merchandise Selection</p>' +
        '<h3 class="dna-b2b-step__title">Which merchandise would you like to create?</h3>' +
        '<p class="dna-b2b-step__copy">Choose as many items as you need. Quantities come on the next screen so this step stays clean and easy to scan.</p>' +
        '<div class="dna-b2b-merch-grid">' + merchCards + '</div>' +
        (state.merchItems.indexOf('other') !== -1
          ? (
            '<div class="dna-b2b-field">' +
              '<label class="dna-b2b-field__label" for="dna-b2b-other-merch">Name your other merchandise</label>' +
              '<input id="dna-b2b-other-merch" type="text" name="custom_merch_text" value="' + escapeHtml(state.customMerchText) + '" placeholder="Example: notebooks, stickers, packaging sleeves">' +
            '</div>'
          )
          : '') +
      '</section>'
    );
  }

  function renderQuantityStep() {
    const rows = state.merchItems.map(function (slug) {
      const label = slug === 'other' && state.customMerchText ? state.customMerchText : (merchOptions[slug] || slug);
      const quantity = state.quantities[slug] || '';

      return (
        '<div class="dna-b2b-quantity" data-quantity-slug="' + escapeHtml(slug) + '">' +
          '<div class="dna-b2b-quantity__label">' + escapeHtml(label) + '</div>' +
          '<div class="dna-b2b-quantity__control">' +
            '<button class="dna-b2b-quantity__button" type="button" data-quantity-adjust="' + escapeHtml(slug) + '" data-direction="-1" aria-label="Decrease quantity for ' + escapeHtml(label) + '">−</button>' +
            '<input class="dna-b2b-quantity__input" type="number" min="1" inputmode="numeric" name="quantity_' + escapeHtml(slug) + '" data-quantity-input="' + escapeHtml(slug) + '" value="' + escapeHtml(quantity) + '" aria-label="Quantity for ' + escapeHtml(label) + '">' +
            '<button class="dna-b2b-quantity__button" type="button" data-quantity-adjust="' + escapeHtml(slug) + '" data-direction="1" aria-label="Increase quantity for ' + escapeHtml(label) + '">+</button>' +
          '</div>' +
        '</div>'
      );
    }).join('');

    return (
      '<section class="dna-b2b-step dna-b2b-step--narrow">' +
        '<p class="dna-b2b-step__kicker">Quantities</p>' +
        '<h3 class="dna-b2b-step__title">How many of each item do you need?</h3>' +
        '<p class="dna-b2b-step__copy">Only the merchandise you selected appears here. Enter the quantity for each item so we can prepare the right quote.</p>' +
        '<div class="dna-b2b-quantity-list">' + rows + '</div>' +
      '</section>'
    );
  }

  function renderMerchUploadsStep() {
    let copy = 'Upload any design files, references, or notes that will help us understand your project.';

    if (state.merchService === 'production_only') {
      copy = 'Upload the design files you already have so we can review them for production and quoting.';
    } else if (state.merchService === 'design_and_production') {
      copy = 'Upload any references, early concepts, or brand files that will help us design and produce the merchandise clearly.';
    } else if (state.merchService === 'design_only') {
      copy = 'Upload references or brand assets if you have them. This step is optional, but helpful.';
    }

    return (
      '<section class="dna-b2b-step dna-b2b-step--narrow">' +
        '<p class="dna-b2b-step__kicker">Project Files</p>' +
        '<h3 class="dna-b2b-step__title">Add files if they will help</h3>' +
        '<p class="dna-b2b-step__copy">' + escapeHtml(copy) + '</p>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-merch-upload">Upload project files</label>' +
          '<input id="dna-b2b-merch-upload" type="file" name="merch_uploads" accept="' + escapeHtml(config.accept) + '" multiple>' +
          '<div class="dna-b2b-field__hint">Accepted files: PDF, JPG, PNG, WEBP, ZIP. You can add files in multiple selections. Up to ' + escapeHtml(String(config.maxFiles)) + ' files, ' + escapeHtml(humanFileSize(config.maxFileSize)) + ' each.</div>' +
          renderFileList(state.merchFiles) +
        '</div>' +
      '</section>'
    );
  }

  function renderEstimateRows(estimate) {
    if (!estimate.items || !estimate.items.length) {
      return '<p class="dna-b2b-step__copy">No merchandise items selected for automatic pricing.</p>';
    }

    return (
      '<div class="dna-b2b-estimate-items">' +
      estimate.items.map(function (item) {
        const qtyControl = item.autoPriced
          ? '<input type="number" min="1" step="1" class="dna-b2b-estimate-item__qty" name="estimate_item_qty_' + escapeHtml(item.id) + '" data-estimate-item-id="' + escapeHtml(item.id) + '" value="' + escapeHtml(String(item.qty || '')) + '">' 
          : '<span class="dna-b2b-estimate-item__manual">Manual quote</span>';

        const productionValue = item.autoPriced
          ? money.format(item.lineProduction || 0)
          : 'Manual quote';

        return (
          '<div class="dna-b2b-estimate-item">' +
            '<div class="dna-b2b-estimate-item__name">' + escapeHtml(item.label) + '</div>' +
            '<div class="dna-b2b-estimate-item__cell">' + qtyControl + '</div>' +
            '<div class="dna-b2b-estimate-item__cell">' + money.format(item.unitCost || 0) + '</div>' +
            '<div class="dna-b2b-estimate-item__cell">' + escapeHtml(String((item.unitWeight || 0).toFixed(3))) + ' kg</div>' +
            '<div class="dna-b2b-estimate-item__cell" data-estimate-item-production="' + escapeHtml(item.id) + '">' + productionValue + '</div>' +
          '</div>'
        );
      }).join('') +
      '</div>'
    );
  }

  function renderEstimateStep() {
    const estimate = state.estimate || createDefaultEstimate();
    const designTiers = getActiveDesignTiers();
    const designOptions = designTiers.map(function (tier) {
      const selected = estimate.designOption === tier.id ? ' selected' : '';
      return '<option value="' + escapeHtml(tier.id) + '"' + selected + '>' + escapeHtml(tier.label + ' — ' + money.format(tier.cost || 0) + '/hr · lead ' + tier.days + ' days') + '</option>';
    }).join('');

    const isLogoPath = state.requestType === 'logo';
    const isDesignOnlyPath = state.requestType === 'merchandise' && state.merchService === 'design_only';
    const showProductionInputs = isMerchProductionPath();
    const designDetails = estimate.designPackage && estimate.designPackage.details
      ? estimate.designPackage.details
      : '';

    const shippingOptions = shippingModes.map(function (mode) {
      const selected = estimate.shippingId === mode.id ? ' selected' : '';
      return '<option value="' + escapeHtml(mode.id) + '"' + selected + '>' + escapeHtml(mode.label + ' — ' + money.format(mode.rate) + '/kg · ' + mode.days + ' days') + '</option>';
    }).join('');

    let modeNote = '';
    if (isLogoPath) {
      modeNote = '<p class="dna-b2b-estimate-note">Logo-only path: production and logistics are excluded from this estimate. Logo design is estimated at 6–12 effective working hours.</p>';
    } else if (isDesignOnlyPath) {
      modeNote = '<p class="dna-b2b-estimate-note">Design-only service: production and logistics are excluded from this estimate. Single merch design is estimated at 1–3 effective working hours per item.</p>';
    } else if (state.requestType === 'merchandise') {
      modeNote = '<p class="dna-b2b-estimate-note">Single merch design is estimated at 1–3 effective working hours per item.</p>';
    }

    const autoPricedCount = (estimate.items || []).filter(function (item) {
      return item.autoPriced;
    }).length;

    const promoCodeDisplay = estimate.promo && estimate.promo.code
      ? estimate.promo.code
      : 'Not applied';
    const promoFeedbackClass = ['success', 'error', 'info'].indexOf(state.promoFeedbackType) !== -1
      ? ' is-' + state.promoFeedbackType
      : '';
    const promoAppliedText = estimate.promo && estimate.promo.code
      ? 'Applied: ' + estimate.promo.code
      : '';

    const productionPanel = showProductionInputs
      ? (
        '<div class="dna-b2b-estimate-block">' +
          '<h4 class="dna-b2b-estimate-block__title">Items</h4>' +
          '<div class="dna-b2b-estimate-head">' +
            '<span>Item</span><span>Qty</span><span>Unit cost</span><span>Unit weight</span><span>Production</span>' +
          '</div>' +
          renderEstimateRows(estimate) +
          (autoPricedCount === 0
            ? '<p class="dna-b2b-estimate-note">No auto-priced items are available. Selected custom item(s) will require manual quote.</p>'
            : '') +
        '</div>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-estimate-production-days">Production time (days)</label>' +
          '<input id="dna-b2b-estimate-production-days" type="number" min="0" step="1" name="estimate_production_days" value="' + escapeHtml(String(estimate.productionDays || 0)) + '">' +
        '</div>' +
        (shippingModes.length
          ? (
            '<div class="dna-b2b-field">' +
              '<label class="dna-b2b-field__label" for="dna-b2b-estimate-shipping">Shipping method</label>' +
              '<select id="dna-b2b-estimate-shipping" name="estimate_shipping_mode">' + shippingOptions + '</select>' +
            '</div>'
          )
          : '<p class="dna-b2b-estimate-note">No shipping mode is configured in WP admin. Logistics is set to $0 until a shipping mode is added.</p>')
      )
      : '';

    return (
      '<section class="dna-b2b-step dna-b2b-step--narrow">' +
        '<p class="dna-b2b-step__kicker">Rough Estimate</p>' +
        '<h3 class="dna-b2b-step__title">Transparent pricing preview</h3>' +
        '<p class="dna-b2b-step__copy">This estimate uses design (hourly rate × effective working hours) + production + 25% profit margin, then adds logistics and tax.</p>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-estimate-design">Design package</label>' +
          '<select id="dna-b2b-estimate-design" name="estimate_design_option">' + designOptions + '</select>' +
        '</div>' +
        (designDetails
          ? '<p class="dna-b2b-estimate-details" data-estimate-out="design-details">' + escapeHtml(designDetails) + '</p>'
          : '<p class="dna-b2b-estimate-details" data-estimate-out="design-details"></p>') +
        '<p class="dna-b2b-estimate-note">Effective working hours include active design/revision/production-coordination work only. Idle, admin, and social time are excluded.</p>' +
        '<div class="dna-b2b-estimate-promo">' +
          '<div class="dna-b2b-field">' +
            '<label class="dna-b2b-field__label" for="dna-b2b-estimate-promo">Promo code</label>' +
            '<div class="dna-b2b-estimate-promo__row">' +
              '<input id="dna-b2b-estimate-promo" type="text" name="estimate_promo_code" value="' + escapeHtml(state.promoInput || '') + '" placeholder="Enter promo code">' +
              '<button type="button" class="btn-ghost dna-b2b-estimate-promo__btn" data-estimate-promo-apply>Apply</button>' +
              '<button type="button" class="btn-ghost dna-b2b-estimate-promo__btn" data-estimate-promo-clear>Clear</button>' +
            '</div>' +
          '</div>' +
          (promoCodes.length
            ? ''
            : '<p class="dna-b2b-estimate-note">No promo codes are active right now.</p>') +
          '<p class="dna-b2b-estimate-promo__applied" data-estimate-out="promo-applied">' + escapeHtml(promoAppliedText) + '</p>' +
          (state.promoFeedback
            ? '<p class="dna-b2b-estimate-promo__feedback' + promoFeedbackClass + '">' + escapeHtml(state.promoFeedback) + '</p>'
            : '<p class="dna-b2b-estimate-promo__feedback' + promoFeedbackClass + '"></p>') +
        '</div>' +
        modeNote +
        productionPanel +
        '<div class="dna-b2b-summary">' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Design hourly rate</div><div class="dna-b2b-summary__value" data-estimate-out="design-hourly-rate">' + money.format(estimate.designHourlyRate || 0) + '/hr</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Effective design hours</div><div class="dna-b2b-summary__value" data-estimate-out="design-effective-hours">' + escapeHtml(formatHours(estimate.designEffectiveHours || 0)) + ' hrs</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Design</div><div class="dna-b2b-summary__value" data-estimate-out="design">' + money.format(estimate.designCost || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Production</div><div class="dna-b2b-summary__value" data-estimate-out="production">' + money.format(estimate.production || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Profit (25%)</div><div class="dna-b2b-summary__value" data-estimate-out="margin">' + money.format(estimate.margin || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Promo code</div><div class="dna-b2b-summary__value" data-estimate-out="promo-code">' + escapeHtml(promoCodeDisplay) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Discount</div><div class="dna-b2b-summary__value" data-estimate-out="discount">' + money.format(estimate.discount || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Logistics & tax</div><div class="dna-b2b-summary__value" data-estimate-out="logistics">' + money.format(estimate.logistics || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Total</div><div class="dna-b2b-summary__value" data-estimate-out="total">' + money.format(estimate.total || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Lead time</div><div class="dna-b2b-summary__value" data-estimate-out="days">' + escapeHtml(String(estimate.totalDays || 0)) + ' days</div></div>' +
        '</div>' +
      '</section>'
    );
  }

  function refreshEstimatePreview() {
    recalculateEstimate();
    const estimate = state.estimate;
    const designHourlyRate = body.querySelector('[data-estimate-out="design-hourly-rate"]');
    const designEffectiveHours = body.querySelector('[data-estimate-out="design-effective-hours"]');
    const design = body.querySelector('[data-estimate-out="design"]');
    const production = body.querySelector('[data-estimate-out="production"]');
    const margin = body.querySelector('[data-estimate-out="margin"]');
    const discount = body.querySelector('[data-estimate-out="discount"]');
    const logistics = body.querySelector('[data-estimate-out="logistics"]');
    const promoCode = body.querySelector('[data-estimate-out="promo-code"]');
    const promoApplied = body.querySelector('[data-estimate-out="promo-applied"]');
    const total = body.querySelector('[data-estimate-out="total"]');
    const days = body.querySelector('[data-estimate-out="days"]');
    const details = body.querySelector('[data-estimate-out="design-details"]');
    const promoFeedback = body.querySelector('.dna-b2b-estimate-promo__feedback');

    if (designHourlyRate) designHourlyRate.textContent = money.format(estimate.designHourlyRate || 0) + '/hr';
    if (designEffectiveHours) designEffectiveHours.textContent = formatHours(estimate.designEffectiveHours || 0) + ' hrs';
    if (design) design.textContent = money.format(estimate.designCost || 0);
    if (production) production.textContent = money.format(estimate.production || 0);
    if (margin) margin.textContent = money.format(estimate.margin || 0);
    if (discount) discount.textContent = money.format(estimate.discount || 0);
    if (logistics) logistics.textContent = money.format(estimate.logistics || 0);
    if (promoCode) promoCode.textContent = (estimate.promo && estimate.promo.code) ? estimate.promo.code : 'Not applied';
    if (promoApplied) promoApplied.textContent = (estimate.promo && estimate.promo.code) ? ('Applied: ' + estimate.promo.code) : '';
    if (total) total.textContent = money.format(estimate.total || 0);
    if (days) days.textContent = String(estimate.totalDays || 0) + ' days';
    if (details) details.textContent = estimate.designPackage && estimate.designPackage.details ? estimate.designPackage.details : '';
    if (promoFeedback) {
      promoFeedback.textContent = state.promoFeedback || '';
      promoFeedback.classList.remove('is-success', 'is-error', 'is-info');
      if (['success', 'error', 'info'].indexOf(state.promoFeedbackType) !== -1) {
        promoFeedback.classList.add('is-' + state.promoFeedbackType);
      }
    }

    const itemNodes = body.querySelectorAll('[data-estimate-item-production]');
    itemNodes.forEach(function (node) {
      const itemId = node.getAttribute('data-estimate-item-production');
      const item = (estimate.items || []).find(function (candidate) {
        return candidate.id === itemId;
      });
      if (!item) return;
      node.textContent = item.autoPriced ? money.format(item.lineProduction || 0) : 'Manual quote';
    });
  }

  function renderContactStep() {
    return (
      '<section class="dna-b2b-step dna-b2b-step--narrow">' +
        '<p class="dna-b2b-step__kicker">Contact</p>' +
        '<h3 class="dna-b2b-step__title">Where should we send the quote?</h3>' +
        '<p class="dna-b2b-step__copy">Share your contact details and timing so we can follow up with a clear next step.</p>' +
        renderSummaryRows() +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-name">Your name</label>' +
          '<input id="dna-b2b-name" type="text" name="name" value="' + escapeHtml(state.name) + '" autocomplete="name">' +
        '</div>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-email">Email</label>' +
          '<input id="dna-b2b-email" type="email" name="email" value="' + escapeHtml(state.email) + '" autocomplete="email">' +
        '</div>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-brand">Brand or business name</label>' +
          '<input id="dna-b2b-brand" type="text" name="brand_name" value="' + escapeHtml(state.brandName) + '" autocomplete="organization">' +
        '</div>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-timeline">Preferred timeline</label>' +
          '<input id="dna-b2b-timeline" type="text" name="timeline" value="' + escapeHtml(state.timeline) + '" placeholder="Example: within 3 weeks, next month, flexible">' +
        '</div>' +
        '<div class="dna-b2b-field">' +
          '<label class="dna-b2b-field__label" for="dna-b2b-notes">Final notes</label>' +
          '<textarea id="dna-b2b-notes" name="notes" placeholder="Anything else we should know before we prepare the quote.">' + escapeHtml(state.notes) + '</textarea>' +
        '</div>' +
        '<div class="dna-b2b-field dna-b2b-visually-hidden" aria-hidden="true">' +
          '<label for="dna-b2b-company-website">Company website</label>' +
          '<input id="dna-b2b-company-website" type="text" name="company_website" value="' + escapeHtml(state.honeypot) + '" tabindex="-1" autocomplete="off">' +
        '</div>' +
      '</section>'
    );
  }

  function renderSuccessStep() {
    const estimate = submittedEstimate;
    const items = estimate && Array.isArray(estimate.items) ? estimate.items : [];

    const itemsHtml = items.length
      ? (
        '<div class="dna-b2b-summary">' +
          items.map(function (item) {
            const value = item.autoPriced
              ? 'Qty ' + item.qty + ' · ' + money.format(item.lineProduction || 0)
              : 'Manual quote';
            return '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">' + escapeHtml(item.label) + '</div><div class="dna-b2b-summary__value">' + escapeHtml(value) + '</div></div>';
          }).join('') +
        '</div>'
      )
      : '';

    const estimateHtml = estimate
      ? (
        '<div class="dna-b2b-summary">' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Design hourly rate</div><div class="dna-b2b-summary__value">' + money.format(estimate.designHourlyRate || 0) + '/hr</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Effective design hours</div><div class="dna-b2b-summary__value">' + escapeHtml(formatHours(estimate.designEffectiveHours || 0)) + ' hrs</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Design</div><div class="dna-b2b-summary__value">' + money.format(estimate.designCost || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Production</div><div class="dna-b2b-summary__value">' + money.format(estimate.production || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Profit (25%)</div><div class="dna-b2b-summary__value">' + money.format(estimate.margin || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Promo code</div><div class="dna-b2b-summary__value">' + escapeHtml((estimate.promo && estimate.promo.code) ? estimate.promo.code : 'Not applied') + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Discount</div><div class="dna-b2b-summary__value">' + money.format(estimate.discount || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Logistics & tax</div><div class="dna-b2b-summary__value">' + money.format(estimate.logistics || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Total rough estimate</div><div class="dna-b2b-summary__value">' + money.format(estimate.total || 0) + '</div></div>' +
          '<div class="dna-b2b-summary__row"><div class="dna-b2b-summary__term">Lead time</div><div class="dna-b2b-summary__value">' + escapeHtml(String(estimate.totalDays || 0)) + ' days</div></div>' +
        '</div>'
      )
      : '';

    return (
      '<section class="dna-b2b-step dna-b2b-step--narrow dna-b2b-success">' +
        '<p class="dna-b2b-step__kicker">Submission Received</p>' +
        '<h3 class="dna-b2b-step__title">Thank you</h3>' +
        '<p class="dna-b2b-step__copy">Thank you, we will have the transparent quote for you shortly.</p>' +
        itemsHtml +
        estimateHtml +
        '<p class="dna-b2b-success__note">Your request has been sent to our team.</p>' +
      '</section>'
    );
  }

  function renderStep(stepId) {
    switch (stepId) {
      case 'request_type':
        return renderRequestTypeStep();
      case 'logo_mode':
        return renderLogoModeStep();
      case 'logo_details':
        return renderLogoDetailsStep();
      case 'merch_service':
        return renderMerchServiceStep();
      case 'merch_items':
        return renderMerchItemsStep();
      case 'merch_quantities':
        return renderQuantityStep();
      case 'merch_uploads':
        return renderMerchUploadsStep();
      case 'estimate':
        return renderEstimateStep();
      case 'contact':
        return renderContactStep();
      default:
        return renderRequestTypeStep();
    }
  }

  function setError(message) {
    errorBox.textContent = message || '';
  }

  function focusFirstInteractive() {
    window.requestAnimationFrame(function () {
      const target = body.querySelector('input:not([type="hidden"]):not([disabled]), textarea:not([disabled]), button:not([disabled]), [href]');
      const fallback = wizard.querySelector('.dna-b2b-wizard__close');
      const nextTarget = target || fallback;

      if (nextTarget && typeof nextTarget.focus === 'function') {
        nextTarget.focus();
      }
    });
  }

  function render() {
    cleanupState();
    applyEstimateProfile();

    if (state.submitted) {
      title.textContent = 'Request received';
      progress.style.width = '100%';
      body.innerHTML = renderSuccessStep();
      backButton.hidden = true;
      nextButton.hidden = true;
      setError('');
      focusFirstInteractive();
      return;
    }

    const steps = getSteps();
    const stepId = getCurrentStep();
    const meta = getStepMeta(stepId);
    const currentNumber = currentStepIndex + 1;
    const total = steps.length;

    title.textContent = meta.title;
    progress.style.width = ((currentNumber / total) * 100) + '%';
    nextButton.textContent = meta.nextLabel;
    nextButton.hidden = false;
    backButton.hidden = currentStepIndex === 0;
    body.innerHTML = renderStep(stepId);
    focusFirstInteractive();
  }

  function showStepError(message, selector) {
    setError(message);

    if (!selector) {
      return false;
    }

    const field = body.querySelector(selector);
    if (field && typeof field.focus === 'function') {
      field.focus();
    }

    return false;
  }

  function validateCurrentStep() {
    const stepId = getCurrentStep();

    setError('');

    if (stepId === 'request_type' && !state.requestType) {
      return showStepError('Choose whether you need logo design or merchandise support.', 'input[name="request_type"]');
    }

    if (stepId === 'logo_mode' && !state.logoMode) {
      return showStepError('Choose how you would like to begin the logo project.', 'input[name="logo_mode"]');
    }

    if (stepId === 'logo_details') {
      if (state.logoMode === 'has_sketch') {
        if (!state.logoFiles.length) {
          return showStepError('Upload at least one sketch or reference file for the logo project.', 'input[name="logo_uploads"]');
        }

        if (!state.logoIdeas.trim()) {
          return showStepError('Tell us your ideas for the logo so we understand what you want to build.', 'textarea[name="logo_ideas"]');
        }
      }

      if (state.logoMode === 'from_scratch' && !state.logoOverview.trim()) {
        return showStepError('Describe your business and the direction you want the logo to carry.', 'textarea[name="logo_overview"]');
      }
    }

    if (stepId === 'merch_service' && !state.merchService) {
      return showStepError('Choose the level of merchandise support you need.', 'input[name="merch_service"]');
    }

    if (stepId === 'merch_items') {
      if (!state.merchItems.length) {
        return showStepError('Select at least one merchandise item to continue.', 'input[name="merch_items[]"]');
      }

      if (state.merchItems.indexOf('other') !== -1 && !state.customMerchText.trim()) {
        return showStepError('Name the other merchandise item so we know what to quote.', 'input[name="custom_merch_text"]');
      }
    }

    if (stepId === 'merch_quantities') {
      for (let i = 0; i < state.merchItems.length; i += 1) {
        const slug = state.merchItems[i];
        const quantity = parseInt(state.quantities[slug], 10);

        if (!quantity || quantity < 1) {
          return showStepError('Enter a quantity for each selected merchandise item.', '[data-quantity-input="' + slug + '"]');
        }
      }
    }

    if (stepId === 'merch_uploads' && state.merchService === 'production_only' && !state.merchFiles.length) {
      return showStepError('Upload your production-ready design files so we can review them for quoting.', 'input[name="merch_uploads"]');
    }

    if (stepId === 'estimate') {
      recalculateEstimate();
      if (isMerchProductionPath()) {
        const autoItems = (state.estimate.items || []).filter(function (item) {
          return item.autoPriced;
        });

        for (let i = 0; i < autoItems.length; i += 1) {
          const item = autoItems[i];
          if (!item.qty || item.qty < 1) {
            return showStepError('Enter quantity for each auto-priced merchandise item.', 'input[name="estimate_item_qty_' + item.id + '"]');
          }
        }

        if (autoItems.length > 0 && !shippingModes.length) {
          return showStepError('No shipping mode is configured. Add one in WP admin: Appearance → DNA B2B Pricing.');
        }
      }
    }

    if (stepId === 'contact') {
      if (!state.name.trim()) {
        return showStepError('Enter your name so we know who to contact.', 'input[name="name"]');
      }

      if (!state.email.trim()) {
        return showStepError('Enter your email so we know where to send the quote.', 'input[name="email"]');
      }

      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(state.email.trim())) {
        return showStepError('Enter a valid email address.', 'input[name="email"]');
      }

      if (!state.brandName.trim()) {
        return showStepError('Enter your brand or business name.', 'input[name="brand_name"]');
      }

      if (!state.timeline.trim()) {
        return showStepError('Tell us your preferred timeline.', 'input[name="timeline"]');
      }
    }

    return true;
  }

  function updateQuantity(slug, nextValue) {
    const quantity = parseInt(nextValue, 10);

    if (!quantity || quantity < 1) {
      state.quantities[slug] = '';
      return;
    }

    state.quantities[slug] = String(quantity);
  }

  function applyPromoFromInput() {
    const code = normalizePromoCode(state.promoInput);

    if (!code) {
      state.promoFeedback = 'Enter a promo code first.';
      state.promoFeedbackType = 'error';
      render();
      return;
    }

    const promo = getPromoByCode(code);
    if (!promo) {
      state.promoFeedback = 'Promo code is invalid or inactive.';
      state.promoFeedbackType = 'error';
      render();
      return;
    }

    state.estimate.promo = {
      code: promo.code,
      type: promo.discountType,
      value: promo.discountValue,
      discountAmount: 0,
    };
    state.promoInput = promo.code;
    state.promoFeedback = 'Promo code applied.';
    state.promoFeedbackType = 'success';
    refreshEstimatePreview();
  }

  function clearPromo() {
    state.estimate.promo = {
      code: '',
      type: '',
      value: 0,
      discountAmount: 0,
    };
    state.promoInput = '';
    state.promoFeedback = 'Promo code removed.';
    state.promoFeedbackType = 'info';
    refreshEstimatePreview();
  }

  async function submitRequest() {
    const uploadFiles = activeFiles();
    const formData = new FormData();
    recalculateEstimate();

    formData.append('dna_nonce', config.nonce || '');
    formData.append('request_type', state.requestType);
    formData.append('logo_mode', state.logoMode);
    formData.append('logo_ideas', state.logoIdeas);
    formData.append('logo_overview', state.logoOverview);
    formData.append('merch_service', state.merchService);
    formData.append('merch_items', JSON.stringify(state.merchItems));
    formData.append('custom_merch_text', state.customMerchText);
    formData.append('quantities', JSON.stringify(state.quantities));
    formData.append('name', state.name);
    formData.append('email', state.email);
    formData.append('brand_name', state.brandName);
    formData.append('timeline', state.timeline);
    formData.append('notes', state.notes);
    formData.append('estimate', JSON.stringify(serializeEstimate(state.estimate)));
    formData.append('company_website', state.honeypot);

    uploadFiles.forEach(function (file) {
      formData.append('uploads[]', file, file.name);
    });

    isSubmitting = true;
    nextButton.disabled = true;
    nextButton.textContent = 'Submitting...';
    setError('');

    try {
      const response = await window.fetch(config.restUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
        },
      });

      let payload = null;

      try {
        payload = await response.json();
      } catch (error) {
        payload = null;
      }

      if (!response.ok) {
        const message = payload && payload.message ? payload.message : 'We could not submit your request. Please review the form and try again.';
        throw new Error(message);
      }

      submittedEstimate = JSON.parse(JSON.stringify(state.estimate || {}));
      state = createDefaultState();
      state.submitted = true;
      currentStepIndex = 0;
      render();
    } catch (error) {
      setError(error && error.message ? error.message : 'We could not submit your request. Please try again.');
    } finally {
      isSubmitting = false;
      nextButton.disabled = false;
      if (!state.submitted) {
        nextButton.textContent = getStepMeta(getCurrentStep()).nextLabel;
      }
    }
  }

  function onNext() {
    if (isSubmitting) {
      return;
    }

    if (!validateCurrentStep()) {
      return;
    }

    if (getCurrentStep() === 'contact') {
      submitRequest();
      return;
    }

    currentStepIndex += 1;
    render();
  }

  function onBack() {
    if (isSubmitting || state.submitted) {
      return;
    }

    currentStepIndex = Math.max(0, currentStepIndex - 1);
    setError('');
    render();
  }

  function openWizard() {
    lastFocused = document.activeElement;
    isOpen = true;
    wizard.hidden = false;
    wizard.setAttribute('aria-hidden', 'false');
    bodyEl.classList.add('dna-b2b-wizard-open');
    setError('');
    render();
  }

  function closeWizard() {
    if (isSubmitting) {
      return;
    }

    isOpen = false;
    wizard.hidden = true;
    wizard.setAttribute('aria-hidden', 'true');
    bodyEl.classList.remove('dna-b2b-wizard-open');
    setError('');

    if (state.submitted) {
      state = createDefaultState();
      currentStepIndex = 0;
      submittedEstimate = null;
    }

    if (lastFocused && typeof lastFocused.focus === 'function') {
      lastFocused.focus();
    }
  }

  function handleCheckboxToggle(input) {
    const value = input.value;
    const next = state.merchItems.slice();

    if (input.checked && next.indexOf(value) === -1) {
      next.push(value);
    }

    if (!input.checked) {
      state.merchItems = next.filter(function (item) {
        return item !== value;
      });
      cleanupState();
      render();
      return;
    }

    state.merchItems = next;
    cleanupState();
    render();
  }

  wizard.addEventListener('click', function (event) {
    const applyPromoButton = event.target.closest('[data-estimate-promo-apply]');
    if (applyPromoButton) {
      applyPromoFromInput();
      return;
    }

    const clearPromoButton = event.target.closest('[data-estimate-promo-clear]');
    if (clearPromoButton) {
      clearPromo();
      return;
    }

    const adjustButton = event.target.closest('[data-quantity-adjust]');
    if (adjustButton) {
      const slug = adjustButton.getAttribute('data-quantity-adjust');
      const direction = parseInt(adjustButton.getAttribute('data-direction'), 10) || 0;
      const current = parseInt(state.quantities[slug], 10) || 0;
      const nextValue = Math.max(1, current + direction);
      state.quantities[slug] = String(nextValue);
      render();
      return;
    }
  });

  wizard.addEventListener('input', function (event) {
    const target = event.target;
    if (!target || !target.name) {
      return;
    }

    if (target.name === 'logo_ideas') {
      state.logoIdeas = target.value;
      return;
    }

    if (target.name === 'logo_overview') {
      state.logoOverview = target.value;
      return;
    }

    if (target.name === 'custom_merch_text') {
      state.customMerchText = target.value;
      return;
    }

    if (target.name === 'name') {
      state.name = target.value;
      return;
    }

    if (target.name === 'email') {
      state.email = target.value;
      return;
    }

    if (target.name === 'brand_name') {
      state.brandName = target.value;
      return;
    }

    if (target.name === 'timeline') {
      state.timeline = target.value;
      return;
    }

    if (target.name === 'notes') {
      state.notes = target.value;
      return;
    }

    if (target.name === 'estimate_production_days') {
      state.estimate.productionDays = toPositiveNumber(target.value, state.estimate.productionDays, 0);
      refreshEstimatePreview();
      return;
    }

    if (target.name === 'estimate_promo_code') {
      state.promoInput = target.value;
      state.promoFeedback = '';
      state.promoFeedbackType = '';
      return;
    }

    if (target.dataset && target.dataset.estimateItemId) {
      const id = target.dataset.estimateItemId;
      updateQuantity(id, target.value);
      refreshEstimatePreview();
      return;
    }

    if (target.name === 'company_website') {
      state.honeypot = target.value;
      return;
    }

    if (target.hasAttribute('data-quantity-input')) {
      updateQuantity(target.getAttribute('data-quantity-input'), target.value);
    }
  });

  wizard.addEventListener('change', function (event) {
    const target = event.target;
    if (!target || !target.name) {
      return;
    }

    if (target.name === 'request_type') {
      state.requestType = target.value;
      setError('');
      currentStepIndex = 0;
      autoAdvanceFromSelection('request_type');
      return;
    }

    if (target.name === 'logo_mode') {
      state.logoMode = target.value;
      autoAdvanceFromSelection('logo_mode');
      return;
    }

    if (target.name === 'merch_service') {
      state.merchService = target.value;
      autoAdvanceFromSelection('merch_service');
      return;
    }

    if (target.name === 'merch_items[]') {
      handleCheckboxToggle(target);
      return;
    }

    if (target.name === 'logo_uploads') {
      state.logoFiles = mergeSelectedFiles(state.logoFiles, Array.from(target.files || []));
      render();
      return;
    }

    if (target.name === 'merch_uploads') {
      state.merchFiles = mergeSelectedFiles(state.merchFiles, Array.from(target.files || []));
      render();
      return;
    }

    if (target.name === 'estimate_design_option') {
      state.estimate.designOption = target.value;
      refreshEstimatePreview();
      return;
    }

    if (target.name === 'estimate_shipping_mode') {
      state.estimate.shippingId = target.value;
      refreshEstimatePreview();
    }
  });

  function trapFocus(event) {
    if (!isOpen || event.key !== 'Tab') {
      return;
    }

    const focusable = Array.prototype.slice.call(wizard.querySelectorAll(focusableSelector))
      .filter(function (node) {
        return node.offsetParent !== null && !node.hasAttribute('hidden') && !node.disabled;
      });

    if (!focusable.length) {
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  document.addEventListener('keydown', function (event) {
    if (!isOpen) {
      return;
    }

    if (event.key === 'Escape') {
      closeWizard();
      return;
    }

    trapFocus(event);
  });

  openButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      openWizard();
    });
  });

  closeButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      closeWizard();
    });
  });

  backButton.addEventListener('click', onBack);
  nextButton.addEventListener('click', onNext);

  if (floatingButton && heroActions && 'IntersectionObserver' in window) {
    floatingButton.hidden = false;

    const observer = new window.IntersectionObserver(function (entries) {
      const entry = entries[0];
      const shouldShow = entry ? !entry.isIntersecting : false;
      floatingButton.classList.toggle('is-visible', shouldShow);
    }, {
      threshold: 0.35,
    });

    observer.observe(heroActions);
  } else if (floatingButton) {
    floatingButton.hidden = false;
    floatingButton.classList.add('is-visible');
  }
})();
