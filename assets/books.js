document.addEventListener('DOMContentLoaded', () => {


    setTimeout(() => {
        const el = document.querySelector('[data-doi]');

        if (el && el.value.trim() === '') {
            el.removeAttribute('readonly');
        }

        jQuery(document).ready(function ($) {
            $('.cf-select__input').select2({ placeholder: 'Escolha uma opção', allowClear: true });
        });

    }, 200)
});

// Função que valida campos obrigatórios
function validarCamposObrigatorios() {
    const msgDiv = document.getElementById('crossref_doi_msg');
    msgDiv.innerHTML = '';

    const doi = document.querySelector("[data-doi]")?.value.trim() || '';
    const titulo = document.getElementById('title')?.value.trim() || '';
    const resumo = document.querySelector('[name="carbon_fields_compact_input[_jats_abstract]"]')?.value.trim() || '';
    const pubDate = document.querySelector('[name="carbon_fields_compact_input[_online_publication_date]"]')?.value.trim() || '';
    const resource = document.querySelector('[name="carbon_fields_compact_input[_resource]"]')?.value.trim() || '';



    const obrigatorios = [];
    if (!titulo) obrigatorios.push('Título');
    if (!doi) obrigatorios.push('DOI');
    if (!resumo) obrigatorios.push('Resumo');
    if (!pubDate) obrigatorios.push('Data de publicação (Versão Eletrônica)');
    if (!resource) obrigatorios.push('Arquivo do Livro');

    // Validação de contribuintes (repeaters)
    const contribsInvalidos = [];
    const contribGivenInputs = document.querySelectorAll('[name^="contributors"][name$="[given]"]');

    contribGivenInputs.forEach(givenInput => {
        const match = givenInput.name.match(/contributors\[(\d+)\]\[given\]/);
        if (!match) return;
        const index = match[1];

        const given = givenInput.value.trim();

        // Somente 'given' é obrigatório
        if (!given) {
            contribsInvalidos.push(parseInt(index) + 1); // número do contribuinte
        }

    });

    let mensagens = [];
    if (obrigatorios.length > 0) {
        mensagens.push('Preencha os campos obrigatórios: ' + obrigatorios.join(', ') + '.');
    }
    if (contribsInvalidos.length > 0) {
        mensagens.push('Preencha todos os campos de contribuintes: #' + contribsInvalidos.join(', ') + '.');
    }

    if (mensagens.length > 0) {
        msgDiv.innerHTML = '<span style="color:red;">' + mensagens.join('<br>') + '</span>';
        return false; // bloqueia ação
    }

    return true;
}

document.addEventListener("DOMContentLoaded", () => {
    // ================= Botão Enviar Doi para a Crossref =================
    setTimeout(() => {
        document.getElementById('crossref_submit_doi')?.addEventListener('click', async function () {

            if (!validarCamposObrigatorios()) return;

            const doi = document.querySelector("[data-doi]").value.trim();
            const msgDiv = document.getElementById('crossref_doi_msg');
            msgDiv.innerHTML = '<span style="color:blue;">Verificando DOI na Crossref...</span>';

            let popupMessage = '';
            let doiExiste = false;

            // Verifica se o DOI já existe na API da crossref
            try {
                const crossrefResponse = await fetch('https://api.crossref.org/works/' + encodeURIComponent(doi));

                if (crossrefResponse.status === 200) {
                    const data = await crossrefResponse.json();
                    const tituloCrossref = data.message?.title?.[0] || 'Sem título';
                    popupMessage = 'Este DOI já está registrado na Crossref.\n\nTítulo: ' + tituloCrossref + '\n\nSe enviar agora, os dados serão sobreescritos.\n\nCaso este seja um livro novo, clique em cancelar.\n';
                    doiExiste = true;
                } else if (crossrefResponse.status === 404) {
                    popupMessage = 'Este DOI ainda não está registrado na Crossref.\n\nAo prosseguir, ele será oficializado.';
                } else {
                    msgDiv.innerHTML = '<span style="color:red;">Erro ao consultar Crossref. Código: ' + crossrefResponse.status + '</span>';
                    return;
                }

            } catch (error) {
                msgDiv.innerHTML = '<span style="color:red;">Erro de conexão: ' + error + '</span>';
                return;
            }

            msgDiv.innerHTML = ''; // limpa a mensagem antes do prompt/confirm

            let proceed = true;

            if (doiExiste) { // DOI já existe
                const userInput = prompt(popupMessage + "Para prosseguir, digite ATUALIZAR:");
                if ((userInput || '').toUpperCase() !== 'ATUALIZAR') {
                    msgDiv.innerHTML = '<span style="color:red;">Envio cancelado pelo usuário.</span>';
                    proceed = false;
                }
            } else { // DOI não existe
                proceed = confirm(popupMessage + "\n\nDeseja enviar para submissão?");
            }

            if (!proceed) return;

            // ================= Envio AJAX para a crossref =================
            const data = new URLSearchParams();

            // action obrigatório e primeiro
            data.append('action', 'send_crossref_book');

            // nonce obrigatório
            data.append('nonce', document.querySelector('[name="nonce"]').value);

            // agora, enviar somente os campos do seu mapa:

            data.append('post_ID', document.getElementById('post_ID').value);


            // envio
            try {
                const ajaxResponse = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: data.toString()
                });

                const result = await ajaxResponse.json();

                if (!ajaxResponse.ok || result.success === false) {
                    msgDiv.innerHTML =
                        '<span style="color:red;">' +
                        (result.data?.message || 'Erro ao enviar.') +
                        '</span>';
                    return;
                }

                console.log(result.data);

                // publicar o post
                document.querySelector('#publish').click();

            } catch (err) {
                alert('Erro ao enviar via AJAX: ' + err);
            }



        });

    });

})

document.addEventListener("readystatechange", () => {

    if (!document.readyState === 'complete') return;


    jQuery(function ($) {
        let frame;

        $('#crossref_resource_file_select').on('click', function (e) {
            e.preventDefault();

            if (!frame) {
                frame = wp.media({
                    title: 'Selecione um arquivo',
                    button: { text: 'Usar este arquivo' },
                    multiple: false
                });

                // Lê o ID sempre no momento da abertura
                frame.on('open', function () {
                    const currentId = $('[name="carbon_fields_compact_input[_resource_id]"]').val();

                    const selection = frame.state().get('selection');
                    selection.reset(); // limpa qualquer seleção prévia

                    if (currentId) {
                        const attachment = wp.media.attachment(currentId);
                        attachment.fetch();
                        selection.add(attachment);
                    }
                });

                // Seleção ao escolher
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();

                    $('[name="carbon_fields_compact_input[_resource]"]').val(attachment.url);
                    $('[name="carbon_fields_compact_input[_resource_id]"]').val(attachment.id);

                    document.querySelector('.media-modal-close')?.click();
                });

                // Sua lógica de fechar
                document.querySelector('.media-modal-close')?.addEventListener('click', () => {
                    setTimeout(() => {
                        document.querySelector('.media-modal-close')?.click();
                    }, 300);
                });
            }

            frame.open();
        });

        // Limpa o ID se o usuário editar a URL manualmente
        const $resource = $('[name="carbon_fields_compact_input[_resource]"]');
        const $resourceId = $('[name="carbon_fields_compact_input[_resource_id]"]');

        $resource.on('input', function () {
            $resourceId.val('');
        });
    });


});