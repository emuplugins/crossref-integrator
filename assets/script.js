document.addEventListener("DOMContentLoaded", () => {

    setTimeout(() => {
        // ================= Botão Gerar DOI =================
        const gerarDoiBtn = document.getElementById('gerar_doi');

        if (gerarDoiBtn) {
            gerarDoiBtn.addEventListener('click', function () {

                const prefixo = '10.48209/';

                function gerarNumero(min, max) {
                    return Math.floor(Math.random() * (max - min + 1)) + min;
                }

                const bloco1 = gerarNumero(100, 999);
                const bloco2 = gerarNumero(10, 99);
                const bloco3 = gerarNumero(1000, 9999);
                const bloco4 = gerarNumero(100, 999);
                const bloco5 = gerarNumero(0, 9);

                const doi = prefixo + bloco1 + '-' + bloco2 + '-' + bloco3 + '-' + bloco4 + '-' + bloco5;
                const campo = document.querySelector("[data-doi]");
                campo.value = doi;

                campo.dispatchEvent(new Event('input', { bubbles: true }));
            });
        }

        // ================= Validador de DOI =================
        const doiField = document.querySelector('[data-doi]');
        if (doiField) {

            const label = doiField.closest('.cf-field').querySelector('.cf-field__label');

            if (label) {
                let span = document.createElement('span');
                span.style.marginLeft = '10px';
                span.style.fontSize = '0.9em';
                label.appendChild(span);

                doiField.addEventListener('input', () => {
                    const original = doiField.value;

                    // somente letras normais, números, ponto, hífen e barra
                    let masked = original.replace(/[^A-Za-z0-9.\-\/]/g, '');

                    if (masked !== original) {
                        doiField.value = masked;
                    }

                    const val = masked.trim();

                    if (val === '') {
                        span.textContent = '';
                        return;
                    }

                    // DOI válido: 10.NUMEROS/sufixo ASCII
                    const regex = /^10\.\d+\/[A-Za-z0-9.\-]+$/;

                    if (regex.test(val)) {
                        span.textContent = 'DOI válido';
                        span.style.color = 'green';
                    } else {
                        span.textContent = 'DOI inválido';
                        span.style.color = 'red';
                    }
                });
            }
        }


        // ================= Monitor de alterações =================
        let isDirty = false;
        const fields = document.querySelectorAll('input, textarea, select');

        fields.forEach(field => {
            field.dataset.initialValue = field.value;

            field.addEventListener('input', () => {
                if (isDirty == false & field.value !== field.dataset.initialValue) {
                    const btn = document.querySelector('#crossref_submit_doi');
                    isDirty = true;
                    if (btn) {

                        const newBtn = btn.cloneNode(true);
                        newBtn.textContent = 'Salve as alterações localmente';
                        btn.parentNode.replaceChild(newBtn, btn);

                        newBtn.addEventListener('click', () => {
                            document.querySelector('#publish').click();
                        });

                        newBtn.classList.add('crossref-disabled');
                    }
                }
            });
        });

        // remove labels vazios
        document.querySelectorAll('label.cf-field__label').forEach(label => {
            if (!label.textContent.trim()) {
                label.remove();
            }
        });

    }, 200);
});



document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {

        function isValidISBN(isbn) {
            isbn = isbn.replace(/[-\s]/g, '');

            if (isbn.length === 10) {
                let sum = 0;
                for (let i = 0; i < 9; i++) {
                    if (isNaN(isbn[i])) return false;
                    sum += (i + 1) * parseInt(isbn[i], 10);
                }
                let check = isbn[9].toUpperCase() === 'X' ? 10 : parseInt(isbn[9], 10);
                sum += 10 * check;
                return sum % 11 === 0;
            }

            if (isbn.length === 13) {
                let sum = 0;
                for (let i = 0; i < 12; i++) {
                    if (isNaN(isbn[i])) return false;
                    sum += parseInt(isbn[i], 10) * (i % 2 === 0 ? 1 : 3);
                }
                let check = (10 - (sum % 10)) % 10;
                return check === parseInt(isbn[12], 10);
            }

            return false;
        }

        const fields = [
            'carbon_fields_compact_input[_isbn_e]',
            'carbon_fields_compact_input[_isbn_p]'
        ];

        fields.forEach(name => {
            const input = document.querySelector(`input[name="${name}"]`);
            if (!input) return;

            const label = input.closest('.cf-field').querySelector('.cf-field__label');
            if (!label) return;

            let span = document.createElement('span');
            span.style.marginLeft = '10px';
            span.style.fontSize = '0.9em';
            span.style.fontWeight = 'normal';
            label.appendChild(span);

            // MÁSCARA: permite apenas números e hífen
            input.addEventListener('input', () => {
                const original = input.value;

                // filtra caracteres proibidos
                let masked = original.replace(/[^0-9-]/g, '');

                // atualiza somente se houve limpeza
                if (masked !== original) {
                    input.value = masked;
                }

                const val = masked.trim();

                if (val === '') {
                    span.textContent = '';
                    return;
                }

                if (isValidISBN(val)) {
                    span.textContent = 'ISBN válido';
                    span.style.color = 'green';
                } else {
                    span.textContent = 'Número inválido';
                    span.style.color = 'red';
                }
            });

        });

    }, 200);
});
