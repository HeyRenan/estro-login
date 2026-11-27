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

  function expandHex(value) {
    const clean = (value || '').replace('#', '').trim();
    if (/^[0-9a-fA-F]{3}$/.test(clean)) {
      return '#' + clean.split('').map((c) => c + c).join('').toLowerCase();
    }
    if (/^[0-9a-fA-F]{6}$/.test(clean)) {
      return '#' + clean.toLowerCase();
    }
    return '';
  }

  function isValidHex(value) {
    return expandHex(value) !== '';
  }

  const form = document.getElementById('estro-login-form');
  const preview = document.getElementById('estro-login-preview');
  const viewport = document.getElementById('el_preview_viewport');

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

  const layoutBadge = document.getElementById('el_preview_layout_badge');
  const logoHeightInput = document.getElementById('el_logo_height');
  const logoHeightVal = document.getElementById('el_logo_height_val');
  const bgOverlayInput = document.getElementById('el_bg_overlay');
  const bgOverlayVal = document.getElementById('el_bg_overlay_val');
  const overlayField = document.querySelector('[data-overlay-field]');
  const welcomeInput = document.getElementById('el_welcome_text');
  const footerInput = document.getElementById('el_footer_text');

  const previewLogoWrap = preview.querySelector('.el-preview__logo');
  const previewLogoImg = previewLogoWrap?.querySelector('img');
  const previewWelcome = preview.querySelector('.el-preview__welcome');
  const previewFooter = preview.querySelector('.el-preview__footer');
  const previewBack = preview.querySelector('.el-preview__back');

  const presetsWrap = form.querySelector('[data-presets]');
  let presets = {};
  if (presetsWrap) {
    try {
      presets = JSON.parse(presetsWrap.getAttribute('data-presets') || '{}');
    } catch (_) {
      presets = {};
    }
  }

  function setColorField(id, value) {
    const hexInput = document.getElementById(id);
    if (!hexInput) {
      return;
    }

    hexInput.value = value;
    const swatch = hexInput.closest('[data-color-control]')?.querySelector('[data-color-swatch]');
    const expanded = expandHex(value);
    if (swatch && expanded) {
      swatch.value = expanded;
    }
  }

  function syncActivePreset(current) {
    if (!presetsWrap) {
      return;
    }

    presetsWrap.querySelectorAll('[data-preset]').forEach((button) => {
      const key = button.getAttribute('data-preset');
      const preset = key ? presets[key] : null;
      const matches = Boolean(
        preset
        && preset.layout === current.layout
        && (preset.bg_color || '').toLowerCase() === current.bgColor.toLowerCase()
        && (preset.bg_color_end || '').toLowerCase() === current.bgEnd.toLowerCase()
        && (preset.card_bg_color || '').toLowerCase() === current.cardBg.toLowerCase()
        && (preset.primary_color || '').toLowerCase() === current.primary.toLowerCase()
        && String(preset.card_radius) === String(current.radius)
      );
      button.classList.toggle('is-active', matches);
    });
  }

  function updatePreview() {
    const layout = form.querySelector('input[name$="[layout]"]:checked')?.value || 'centered';
    preview.classList.remove('el-preview--centered', 'el-preview--split', 'el-preview--minimal');
    preview.classList.add(`el-preview--${layout}`);

    if (layoutBadge) {
      layoutBadge.textContent = layoutLabels[layout] || layout;
    }

    const rawBg = document.getElementById('el_bg_color')?.value || '';
    const bgColor = isValidHex(rawBg) ? rawBg.trim() : '#f1f5f9';
    const rawBgEnd = document.getElementById('el_bg_color_end')?.value.trim() || '';
    const bgEnd = isValidHex(rawBgEnd) ? rawBgEnd : '';
    const rawCardBg = document.getElementById('el_card_bg_color')?.value || '';
    const cardBg = isValidHex(rawCardBg) ? rawCardBg.trim() : '#ffffff';
    const rawPrimary = document.getElementById('el_primary_color')?.value || '';
    const primary = isValidHex(rawPrimary) ? rawPrimary.trim() : '#0f172a';
    const radius = form.querySelector('input[name$="[card_radius]"]:checked')?.value || '8';
    const logoHeight = logoHeightInput?.value || '64';
    const overlay = Number(bgOverlayInput?.value || '45');
    const isMobilePreview = viewport?.classList.contains('el-preview-viewport--mobile') || false;

    if (logoHeightVal) {
      logoHeightVal.textContent = `${logoHeight}px`;
    }
    if (bgOverlayVal) {
      bgOverlayVal.textContent = `${overlay}%`;
    }

    const bgImgEl = form.querySelector('[data-media-picker="bg"] img');
    const bgImgUrl = bgImgEl && !bgImgEl.closest('.is-empty') ? bgImgEl.getAttribute('src') || '' : '';
    preview.classList.toggle('is-has-bg-image', bgImgUrl !== '');
    overlayField?.classList.toggle('is-muted', bgImgUrl === '');

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
    const hasBodyBgImage = bgImgUrl !== '' && layout !== 'split';
    const bgContrastSample = bgEnd !== '' ? bgEnd : bgColor;
    const outerSource = (hasBodyBgImage && overlay >= 35) ? '#0f172a' : bgContrastSample;
    const outerText = (layout === 'split' && !isMobilePreview)
      ? cardText
      : contrastColor(outerSource, '#ffffff', '#0f172a');

    preview.style.setProperty('--el-bg', bgColor);
    preview.style.setProperty('--el-bg-surface', bgSurface);
    preview.style.setProperty('--el-card-bg', cardBg);
    preview.style.setProperty('--el-card-text', cardText);
    preview.style.setProperty('--el-card-border', cardBorder);
    preview.style.setProperty('--el-primary', primary);
    preview.style.setProperty('--el-primary-text', primaryText);
    preview.style.setProperty('--el-outer-text', outerText);
    preview.style.setProperty('--el-radius', `${radius}px`);
    preview.style.setProperty('--el-logo-height', `${logoHeight}px`);
    const safeBgImgUrl = bgImgUrl.replace(/["\\]/g, '');
    preview.style.setProperty('--el-bg-image', safeBgImgUrl ? `url("${safeBgImgUrl}")` : 'none');
    preview.style.setProperty('--el-overlay-alpha', safeBgImgUrl ? (overlay / 100).toFixed(2) : '0');

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

    syncActivePreset({ layout, bgColor, bgEnd, cardBg, primary, radius });
  }

  presetsWrap?.querySelectorAll('[data-preset]').forEach((button) => {
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

      setColorField('el_bg_color', preset.bg_color || '#f1f5f9');
      setColorField('el_bg_color_end', preset.bg_color_end || '');
      setColorField('el_card_bg_color', preset.card_bg_color || '#ffffff');
      setColorField('el_primary_color', preset.primary_color || '#0f172a');

      const radiusRadio = form.querySelector(`input[name$="[card_radius]"][value="${preset.card_radius}"]`);
      if (radiusRadio) {
        radiusRadio.checked = true;
      }

      updatePreview();
    });
  });

  document.querySelectorAll('[data-preview-mode]').forEach((modeBtn) => {
    modeBtn.addEventListener('click', () => {
      const mode = modeBtn.getAttribute('data-preview-mode');
      document.querySelectorAll('[data-preview-mode]').forEach((btn) => {
        btn.setAttribute('aria-pressed', btn === modeBtn ? 'true' : 'false');
      });
      viewport?.classList.toggle('el-preview-viewport--mobile', mode === 'mobile');
      updatePreview();
    });
  });

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
      const expanded = expandHex(hexInput.value);
      if (expanded && swatch) {
        swatch.value = expanded;
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
    const previewBox = picker.querySelector('.el-media__preview');
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

  const transferWrap = form.querySelector('[data-transfer]');
  if (transferWrap) {
    const portableKeys = [
      'layout',
      'logo_height',
      'bg_color',
      'bg_color_end',
      'bg_overlay',
      'card_bg_color',
      'primary_color',
      'card_radius',
      'welcome_text',
      'footer_text',
      'hide_language_switcher',
      'hide_back_to_blog',
      'generic_login_errors',
      'custom_css',
    ];
    const exportBtn = transferWrap.querySelector('[data-transfer-export]');
    const importBtn = transferWrap.querySelector('[data-transfer-import]');
    const fileInput = transferWrap.querySelector('[data-transfer-file]');
    const statusEl = transferWrap.querySelector('[data-transfer-status]');

    function showTransferStatus(message, isError = false) {
      if (!statusEl) {
        return;
      }
      statusEl.textContent = message;
      statusEl.classList.toggle('is-error', isError);
      statusEl.hidden = !message;
    }

    function collectPortableSettings() {
      const toggle = (name) => Boolean(form.querySelector(`input[name$="[${name}]"]`)?.checked);
      return {
        layout: form.querySelector('input[name$="[layout]"]:checked')?.value || 'centered',
        logo_height: Number(logoHeightInput?.value || 64),
        bg_color: document.getElementById('el_bg_color')?.value.trim() || '#f1f5f9',
        bg_color_end: document.getElementById('el_bg_color_end')?.value.trim() || '',
        bg_overlay: Number(bgOverlayInput?.value || 45),
        card_bg_color: document.getElementById('el_card_bg_color')?.value.trim() || '#ffffff',
        primary_color: document.getElementById('el_primary_color')?.value.trim() || '#0f172a',
        card_radius: Number(form.querySelector('input[name$="[card_radius]"]:checked')?.value || 8),
        welcome_text: welcomeInput?.value || '',
        footer_text: footerInput?.value || '',
        hide_language_switcher: toggle('hide_language_switcher'),
        hide_back_to_blog: toggle('hide_back_to_blog'),
        generic_login_errors: toggle('generic_login_errors'),
        custom_css: document.getElementById('el_custom_css')?.value || '',
      };
    }

    function applyImportedSettings(data) {
      if (typeof data.layout === 'string') {
        const radio = form.querySelector(`input[name$="[layout]"][value="${CSS.escape(data.layout)}"]`);
        if (radio) {
          radio.checked = true;
        }
      }

      if (data.logo_height !== undefined && logoHeightInput) {
        const val = Math.max(24, Math.min(160, Math.round(Number(data.logo_height))));
        if (!Number.isNaN(val)) {
          logoHeightInput.value = String(val);
        }
      }

      if (data.bg_overlay !== undefined && bgOverlayInput) {
        const val = Math.max(0, Math.min(90, Math.round(Number(data.bg_overlay))));
        if (!Number.isNaN(val)) {
          bgOverlayInput.value = String(val);
        }
      }

      ['bg_color', 'card_bg_color', 'primary_color'].forEach((key) => {
        if (typeof data[key] === 'string' && expandHex(data[key])) {
          setColorField(`el_${key}`, expandHex(data[key]));
        }
      });

      if (typeof data.bg_color_end === 'string') {
        const trimmed = data.bg_color_end.trim();
        if (trimmed === '') {
          setColorField('el_bg_color_end', '');
        } else if (expandHex(trimmed)) {
          setColorField('el_bg_color_end', expandHex(trimmed));
        }
      }

      if (data.card_radius !== undefined) {
        const radiusRadio = form.querySelector(`input[name$="[card_radius]"][value="${CSS.escape(String(data.card_radius))}"]`);
        if (radiusRadio) {
          radiusRadio.checked = true;
        }
      }

      if (typeof data.welcome_text === 'string' && welcomeInput) {
        welcomeInput.value = data.welcome_text.slice(0, 160);
      }

      if (typeof data.footer_text === 'string' && footerInput) {
        footerInput.value = data.footer_text.slice(0, 160);
      }

      ['hide_language_switcher', 'hide_back_to_blog', 'generic_login_errors'].forEach((key) => {
        if (data[key] !== undefined) {
          const checkbox = form.querySelector(`input[type="checkbox"][name$="[${key}]"]`);
          if (checkbox) {
            checkbox.checked = Boolean(data[key]);
          }
        }
      });

      const customCssInput = document.getElementById('el_custom_css');
      if (typeof data.custom_css === 'string' && customCssInput) {
        customCssInput.value = data.custom_css;
      }

      updatePreview();
    }

    exportBtn?.addEventListener('click', () => {
      const payload = {
        plugin: 'estro-login',
        version: transferWrap.getAttribute('data-version') || '1.2.0',
        settings: collectPortableSettings(),
      };
      const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = 'estro-login-settings.json';
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
      showTransferStatus(transferWrap.getAttribute('data-msg-exported') || 'Settings exported to JSON.');
    });

    importBtn?.addEventListener('click', () => {
      fileInput?.click();
    });

    fileInput?.addEventListener('change', async () => {
      const file = fileInput.files?.[0];
      if (!file) {
        return;
      }

      const invalidMsg = transferWrap.getAttribute('data-msg-invalid') || 'Invalid Estro Login JSON file.';
      try {
        const parsed = JSON.parse(await file.text());
        const candidate = (parsed && typeof parsed === 'object' && !Array.isArray(parsed) && parsed.settings && typeof parsed.settings === 'object' && !Array.isArray(parsed.settings))
          ? parsed.settings
          : parsed;

        if (!candidate || typeof candidate !== 'object' || Array.isArray(candidate)) {
          showTransferStatus(invalidMsg, true);
          return;
        }

        const hasKnownKey = portableKeys.some((key) => Object.prototype.hasOwnProperty.call(candidate, key));
        if (!hasKnownKey) {
          showTransferStatus(invalidMsg, true);
          return;
        }

        applyImportedSettings(candidate);
        showTransferStatus(transferWrap.getAttribute('data-msg-imported') || 'Settings loaded into preview. Click "Save changes" to apply.');
      } catch (_) {
        showTransferStatus(invalidMsg, true);
      } finally {
        fileInput.value = '';
      }
    });
  }

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
