/*
|--------------------------------------------------------------------------
| MÁSCARA DE TELEFONE BRASILEIRO
|--------------------------------------------------------------------------
| Formata automaticamente o campo de telefone no padrão:
| (XX) XXXXX-XXXX (celular - 11 dígitos)
| (XX) XXXX-XXXX (fixo - 10 dígitos)
*/

function mascaraTelefone(input) {
    let valor = input.value.replace(/\D/g, '');

    if (valor.length > 11) {
        valor = valor.substring(0, 11);
    }

    if (valor.length > 0) {
        valor = '(' + valor;
    }

    if (valor.length > 3) {
        valor = valor.substring(0, 3) + ') ' + valor.substring(3);
    }

    if (valor.length > 10) {
        /* Celular: (XX) XXXXX-XXXX */
        valor = valor.substring(0, 10) + '-' + valor.substring(10);
    } else if (valor.length > 9) {
        /* Fixo: (XX) XXXX-XXXX */
        valor = valor.substring(0, 10) + '-' + valor.substring(10);
    }

    input.value = valor;
}

/*
|--------------------------------------------------------------------------
| INICIALIZAÇÃO
|--------------------------------------------------------------------------
| Aplica a máscara em todos os campos com classe .phone-mask
| e também nos campos de telefone por name="phone".
*/

/*
|--------------------------------------------------------------------------
| CAMPOS DE TEXTO EM MINÚSCULAS
|--------------------------------------------------------------------------
| Aplica transformação automática para minúsculas em campos
| com a classe .lowercase-input.
*/

document.addEventListener('DOMContentLoaded', function() {
    const camposLowercase = document.querySelectorAll('.lowercase-input');

    camposLowercase.forEach(function(campo) {
        campo.addEventListener('input', function() {
            this.value = this.value.toLowerCase();
        });

        if (campo.value) {
            campo.value = campo.value.toLowerCase();
        }
    });

    const camposTelefone = document.querySelectorAll('input[name="phone"]');

    camposTelefone.forEach(function(campo) {
        /* Aplica a máscara a cada tecla pressionada */
        campo.addEventListener('input', function() {
            mascaraTelefone(this);
        });

        /* Formata o valor ao carregar a página (edição) */
        if (campo.value) {
            mascaraTelefone(campo);
        }
    });

    /*
    |--------------------------------------------------------------------------
    | MÁSCARA DE CEP
    |--------------------------------------------------------------------------
    */
    const camposCep = document.querySelectorAll('input[name="cep"]');

    /* Rastrear edição manual do campo endereço */
    const addressField = document.getElementById('address');
    if (addressField) {
        addressField.addEventListener('input', function() {
            this.dataset.userEdited = 'true';
        });
        /* Resetar quando o CEP mudar (novo CEP = novo auto-fill) */
        camposCep.forEach(function(campo) {
            campo.addEventListener('input', function() {
                if (addressField) {
                    addressField.dataset.userEdited = 'false';
                }
            });
        });
    }

    camposCep.forEach(function(campo) {
        campo.addEventListener('input', function() {
            let valor = this.value.replace(/\D/g, '');
            if (valor.length > 8) valor = valor.substring(0, 8);
            if (valor.length > 5) {
                valor = valor.substring(0, 5) + '-' + valor.substring(5);
            }
            this.value = valor;
        });

        if (campo.value) {
            let valor = campo.value.replace(/\D/g, '');
            if (valor.length > 5) {
                valor = valor.substring(0, 5) + '-' + valor.substring(5);
            }
            campo.value = valor;
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTA À API DO VIACEP
        |--------------------------------------------------------------------------
        */
        campo.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            if (cep.length !== 8) return;

            const addressField = document.getElementById('address');
            if (!addressField) return;

            /* Não sobrescrever se o usuário editou manualmente */
            if (addressField.dataset.userEdited === 'true') return;

            addressField.value = 'Buscando endereço...';
            addressField.style.opacity = '0.6';

            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                .then(function(resp) { return resp.json(); })
                .then(function(data) {
                    addressField.style.opacity = '1';
                    if (data.erro) {
                        addressField.value = '';
                        addressField.placeholder = 'CEP não encontrado. Digite o endereço manualmente.';
                        return;
                    }
                    let endereco = data.logradouro;
                    if (data.bairro) endereco += ', ' + data.bairro;
                    if (data.localidade) endereco += ' - ' + data.localidade;
                    if (data.uf) endereco += '/' + data.uf;
                    addressField.value = endereco;
                    addressField.placeholder = 'Confirme ou complemente o endereço';
                })
                .catch(function() {
                    addressField.style.opacity = '1';
                    addressField.value = '';
                    addressField.placeholder = 'Erro ao buscar CEP. Digite o endereço manualmente.';
                });
        });
    });
});
