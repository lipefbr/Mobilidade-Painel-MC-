/**
 * Script para validação e formatação de CPF
 */

document.addEventListener('DOMContentLoaded', function() {
    // Seleciona o campo de CPF
    const cpfInput = document.getElementById('cpf');
    
    if (cpfInput) {
        // Adiciona evento para formatar o CPF enquanto o usuário digita
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value;
            
            // Remove todos os caracteres não numéricos
            value = value.replace(/\D/g, '');
            
            // Aplica a máscara de CPF: XXX.XXX.XXX-XX
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            
            // Atualiza o valor do campo
            e.target.value = value;
        });
        
        // Adiciona evento para validar o CPF quando o campo perder o foco
        cpfInput.addEventListener('blur', function(e) {
            const cpf = e.target.value.replace(/\D/g, '');
            
            if (cpf.length !== 11) {
                alert('CPF inválido. O CPF deve conter 11 dígitos.');
                return;
            }
            
            // Verifica se todos os dígitos são iguais
            if (/^(\d)\1+$/.test(cpf)) {
                alert('CPF inválido.');
                return;
            }
            
            // Calcula o primeiro dígito verificador
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = 11 - (soma % 11);
            let dv1 = resto > 9 ? 0 : resto;
            
            // Verifica o primeiro dígito verificador
            if (parseInt(cpf.charAt(9)) !== dv1) {
                alert('CPF inválido.');
                return;
            }
            
            // Calcula o segundo dígito verificador
            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            resto = 11 - (soma % 11);
            let dv2 = resto > 9 ? 0 : resto;
            
            // Verifica o segundo dígito verificador
            if (parseInt(cpf.charAt(10)) !== dv2) {
                alert('CPF inválido.');
                return;
            }
        });
    }

    function formatCpf(cpf){
        // Remove todos os caracteres não numéricos
        cpf = cpf.replace(/\D/g, '');
        
        // Aplica a máscara de CPF: XXX.XXX.XXX-XX
        if (cpf.length <= 11) {
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        
        return cpf;
    }
});
