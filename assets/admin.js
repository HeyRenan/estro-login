document.addEventListener('DOMContentLoaded', () => {
  function contrastColor(hex, light = '#ffffff', dark = '#0f172a') {
    const clean = (hex || '').replace('#', '').trim();
    const full = clean.length === 3
      ? clean.split('').map((c) => c + c).join('')
      : clean;

    if (!/^[0-9a-fA-F]{6}$/.test(full)) {
      return dark;
    }

    const channels = [
      parseInt(full.slice(0, 2), 16) / 255,
      parseInt(full.slice(2, 4), 16) / 255,
      parseInt(full.slice(4, 6), 16) / 255,
    ];

    const linear = channels.map((c) => (c <= 0.04045 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4));
    const lum = 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2];

    return lum < 0.198 ? light : dark;
  }

  function isValidHex(value) {
    return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test((value || '').trim());
  }

  const form = document.getElementById('logincraft-form');
  const preview = document.getElementById('logincraft-preview');

  if (!form || !preview) {
    return;
  }

  let layoutLabels = { centered: 'Centered', split: 'Split', minimal: 'Minimal' };
  try {
    if (preview.dataset.layoutLabels) {
      layoutLabels = JSON.parse(preview.dataset.layoutLabels);
    }
  } catch (_) {
    // Keep fallback labels if attribute JSON is malformed.
  }

  const layoutBadge = document.getElementById('lc_preview_layout_badge');
  const logoHeightInput = document.getElementById('lc_logo_height');
  const logoHeightVal = document.getElementById('lc_logo_height_val');
  const bgOverlayInput = document.getElementById('lc_bg_overlay');
  const bgOverlayVal = document.getElementById('lc_bg_overlay_val');
  const welcomeInput = document.getElementById('lc_welcome_text');
  const footerInput = document.getElementById('lc_footer_text');

  const previewLogoWrap = preview.querySelector('.lc-preview__logo');
  const previewLogoImg = previewLogoWrap?.querySelector('img');
  const previewWelcome = preview.querySelector('.lc-preview__welcome');
  const previewFooter = preview.querySelector('.lc-preview__footer');
  const previewBack = preview.querySelector('.lc-preview__back');

  function setColorField(id, value) {
    const hexInput = document.getElementById(id);
    if (!hexInput) {
      return;
    }

    hexInput.value = value;
    const swatch = hexInput.closest('[data-color-control]')?.querySelector('[data-color-swatch]');
    if (swatch && isValidHex(value)) {
      swatch.value = value;
    }
  }

  function updatePreview() {
    const layout = form.querySelector('input[name$="[layout]"]:checked')?.value || 'centered';
    preview.classList.remove('lc-preview--centered', 'lc-preview--split', 'lc-preview--minimal');
    preview.classList.add(`lc-preview--${layout}`);

    if (layoutBadge) {
      layoutBadge.textContent = layoutLabels[layout] || layout;
    }

    const rawBg = document.getElementById('lc_bg_color')?.value || '';
    const bgColor = isValidHex(rawBg) ? rawBg.trim() : '#f1f5f9';
    const rawBgEnd = document.getElementById('lc_bg_color_end')?.value.trim() || '';
    const bgEnd = isValidHex(rawBgEnd) ? rawBgEnd : '';
    const rawCardBg = document.getElementById('lc_card_bg_color')?.value || '';
    const cardBg = isValidHex(rawCardBg) ? rawCardBg.trim() : '#ffffff';
    const rawPrimary = document.getElementById('lc_primary_color')?.value || '';
    const primary = isValidHex(rawPrimary) ? rawPrimary.trim() : '#0f172a';
    const radius = form.querySelector('input[name$="[card_radius]"]:checked')?.value || '8';
    const logoHeight = logoHeightInput?.value || '64';
    const overlay = Number(bgOverlayInput?.value || '45');

    if (logoHeightVal) {
      logoHeightVal.textContent = `${logoHeight}px`;
    }
    if (bgOverlayVal) {
      bgOverlayVal.textContent = `${overlay}%`;
    }

    const bgImgEl = form.querySelector('[data-media-picker="bg"] img');
    const bgImgUrl = bgImgEl && !bgImgEl.closest('.is-empty') ? bgImgEl.getAttribute('src') || '' : '';
    preview.classList.toggle('is-has-bg-image', bgImgUrl !== '');

    const logoEl = form.querySelector('[data-media-picker="logo"] img');
    const selectedLogo = logoEl && !logoEl.closest('.is-empty') ? logoEl.getAttribute('src') || '' : '';
    const effectiveLogo = selectedLogo || preview.dataset.fallbackLogo || '';

    if (previewLogoWrap && previewLogoImg) {
      if (effectiveLogo) {
        previewLogoImg.src = effectiveLogo;
        previewLogoWrap.classList.remove('is-fallback');
      } else {
        previewLogoImg.src = '';
        previewLogoWrap.classList.add('is-fallback');
      }
    }

    const bgSurface = bgEnd !== ''
      ? `linear-gradient(135deg, ${bgColor} 0%, ${bgEnd} 100%)`
      : bgColor;

    const cardText = contrastColor(cardBg, '#f8fafc', '#0f172a');
    const cardBorder = cardText === '#f8fafc' ? 'rgb(255 255 255 / 0.14)' : 'rgb(15 23 42 / 0.08)';
    const primaryText = contrastColor(primary);
    const bgContrastSample = bgEnd !== '' ? bgEnd : bgColor;
    const outerSource = (bgImgUrl && overlay >= 35) ? '#0f172a' : bgContrastSample;
    const outerText = layout === 'split' ? cardText : contrastColor(outerSource, '#ffffff', '#0f172a');

    preview.style.setProperty('--lc-bg', bgColor);
    preview.style.setProperty('--lc-bg-surface', bgSurface);
    preview.style.setProperty('--lc-card-bg', cardBg);
    preview.style.setProperty('--lc-card-text', cardText);
    preview.style.setProperty('--lc-card-border', cardBorder);
    preview.style.setProperty('--lc-primary', primary);
    preview.style.setProperty('--lc-primary-text', primaryText);
    preview.style.setProperty('--lc-outer-text', outerText);
    preview.style.setProperty('--lc-radius', `${radius}px`);
    preview.style.setProperty('--lc-logo-height', `${logoHeight}px`);
    preview.style.setProperty('--lc-bg-image', bgImgUrl ? `url("${bgImgUrl}")` : 'none');
    preview.style.setProperty('--lc-overlay-alpha', bgImgUrl ? (overlay / 100).toFixed(2) : '0');

    if (previewWelcome && welcomeInput) {
      const text = welcomeInput.value.trim();
      previewWelcome.textContent = text;
      previewWelcome.hidden = text === '';
    }

    if (previewFooter && footerInput) {
      const text = footerInput.value.trim();
      previewFooter.textContent = text;
      previewFooter.hidden = text === '';
    }

    const hideBack = form.querySelector('input[name$="[hide_back_to_blog]"]')?.checked || false;
    if (previewBack) {
      previewBack.hidden = hideBack;
    }
  }

  const presetsWrap = form.querySelector('[data-presets]');
  if (presetsWrap) {
    let presets = {};
    try {
      presets = JSON.parse(presetsWrap.getAttribute('data-presets') || '{}');
    } catch (_) {
      presets = {};
    }

    presetsWrap.querySelectorAll('[data-preset]').forEach((button) => {
      button.addEventListener('click', () => {
        const key = button.getAttribute('data-preset');
        const preset = key ? presets[key] : null;
        if (!preset) {
          return;
        }

        const layoutRadio = form.querySelector(`input[name$="[layout]"][value="${preset.layout}"]`);
        if (layoutRadio) {
          layoutRadio.checked = true;
        }

        setColorField('lc_bg_color', preset.bg_color || '#f1f5f9');
        setColorField('lc_bg_color_end', preset.bg_color_end || '');
        setColorField('lc_card_bg_color', preset.card_bg_color || '#ffffff');
        setColorField('lc_primary_color', preset.primary_color || '#0f172a');

        const radiusRadio = form.querySelector(`input[name$="[card_radius]"][value="${preset.card_radius}"]`);
        if (radiusRadio) {
          radiusRadio.checked = true;
        }

        updatePreview();
      });
    });
  }

  form.querySelectorAll('[data-color-control]').forEach((group) => {
    const swatch = group.querySelector('[data-color-swatch]');
    const hexInput = group.querySelector('[data-color-hex]');
    const clearBtn = group.querySelector('[data-color-clear]');

    swatch?.addEventListener('input', () => {
      if (hexInput) {
        hexInput.value = swatch.value;
      }
      updatePreview();
    });

    hexInput?.addEventListener('input', () => {
      if (isValidHex(hexInput.value) && swatch) {
        swatch.value = hexInput.value.trim();
      }
      updatePreview();
    });

    clearBtn?.addEventListener('click', () => {
      if (hexInput) {
        hexInput.value = '';
      }
      updatePreview();
    });
  });

  form.querySelectorAll('[data-media-picker]').forEach((picker) => {
    const input = picker.querySelector('input[type="hidden"]');
    const previewBox = picker.querySelector('.lc-media__preview');
    const previewImg = previewBox?.querySelector('img');
    const selectBtn = picker.querySelector('[data-media-select]');
    const removeBtn = picker.querySelector('[data-media-remove]');
    const modalTitle = picker.getAttribute('data-media-title') || 'Select image';
    const modalButton = picker.getAttribute('data-media-button') || 'Use image';
    let frame = null;

    selectBtn?.addEventListener('click', (event) => {
      event.preventDefault();
      if (!window.wp?.media) {
        return;
      }

      if (frame) {
        frame.open();
        return;
      }

      frame = window.wp.media({
        title: modalTitle,
        multiple: false,
        library: { type: 'image' },
        button: { text: modalButton },
      });

      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const url = attachment.sizes?.large?.url || attachment.sizes?.medium?.url || attachment.url || '';

        if (input) {
          input.value = String(attachment.id || 0);
        }
        if (previewImg && previewBox) {
          previewImg.src = url;
          previewBox.classList.toggle('is-empty', url === '');
        }
        if (removeBtn) {
          removeBtn.hidden = url === '';
        }
        updatePreview();
      });

      frame.open();
    });

    removeBtn?.addEventListener('click', (event) => {
      event.preventDefault();
      if (input) {
        input.value = '0';
      }
      if (previewImg && previewBox) {
        previewImg.src = '';
        previewBox.classList.add('is-empty');
      }
      removeBtn.hidden = true;
      selectBtn?.focus();
      updatePreview();
    });
  });

  form.addEventListener('input', updatePreview);
  form.addEventListener('change', updatePreview);

  const resetBtn = document.querySelector('[data-confirm-reset]');
  resetBtn?.addEventListener('click', (event) => {
    const message = resetBtn.getAttribute('data-confirm-reset') || 'Restore defaults?';
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  });

  updatePreview();
});
