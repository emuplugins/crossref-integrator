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
            });
        }

        let isDirty = false;
        // Seleciona todos os campos do post
        const fields = document.querySelectorAll('input, textarea, select');

        // Armazena o valor inicial de cada campo
        fields.forEach(field => {
            field.dataset.initialValue = field.value;

            field.addEventListener('input', () => {
                if (isDirty == false & field.value !== field.dataset.initialValue) {
                    const btn = document.querySelector('#crossref_submit_doi');
                    isDirty = true;
                    if (btn) {

                        // Substitui o botão por um clone
                        const newBtn = btn.cloneNode(true);
                        newBtn.textContent = 'Salve as alterações localmente'; // altera o texto
                        btn.parentNode.replaceChild(newBtn, btn);


                        newBtn.addEventListener('click', () => {
                            document.querySelector('#publish').click();

                        })

                        setTimeout(() => {
                            newBtn.classList.add('crossref-disabled')
                        }, 200)
                    }
                }
            });
        });



    })
})

