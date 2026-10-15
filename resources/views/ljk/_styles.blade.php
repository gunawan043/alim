@include('kurikulum._styles')

<style>
    /* Konvensi tambahan khusus modul LJK — mengikuti pola gtk/index.blade.php */

    /* Chip skor per opsi jawaban (hasil baca OMR) */
    .ljk-option-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
    }
    .ljk-option-chip.is-read { background: #198754; border-color: #198754; color: #fff; }
    .ljk-option-chip.is-candidate { background: #fff3cd; border-color: #ffe69c; color: #997404; }

    /* Baris jawaban yang perlu diperiksa */
    .ljk-row-review { background: #fff8e6; }

    /* Kartu panduan pemindaian */
    .ljk-guide-step {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 8px 0;
        border-bottom: 1px dashed #e2e8f0;
        font-size: 13px;
        color: #475569;
    }
    .ljk-guide-step:last-child { border-bottom: none; }
    .ljk-guide-step .step-no {
        flex-shrink: 0;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #e7f5fd;
        color: #0a5f9e;
        font-size: 12px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* Bingkai kamera */
    .ljk-camera-shell {
        position: relative;
        background: #0f172a;
        border-radius: 12px;
        overflow: hidden;
        aspect-ratio: 3 / 4;
        max-height: 70vh;
    }
    .ljk-camera-shell video { width: 100%; height: 100%; object-fit: cover; }
    .ljk-camera-guide {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    .ljk-camera-guide .frame {
        width: 74%;
        height: 88%;
        border: 3px dashed rgba(255, 255, 255, 0.85);
        border-radius: 8px;
        position: relative;
    }
    .ljk-camera-guide .frame .hint {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 11px;
    }

    /* Kotak petunjuk penanda sudut pada panduan */
    .ljk-corner {
        position: absolute;
        width: 16px;
        height: 16px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 2px;
    }
    .ljk-corner.tl { top: 8px; left: 8px; }
    .ljk-corner.tr { top: 8px; right: 8px; }
    .ljk-corner.bl { bottom: 8px; left: 8px; }
    .ljk-corner.br { bottom: 8px; right: 8px; }

    /* Panel hasil pemindaian */
    .ljk-result-badge { font-size: 11px; }
    .ljk-answer-grid { max-height: 220px; overflow: auto; display: flex; flex-wrap: wrap; gap: 4px; }
</style>
