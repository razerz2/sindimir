<script>
    document.addEventListener('DOMContentLoaded', function () {
        const onlyDigits = (value) => (value || '').replace(/\D+/g, '');

        const maskCpf = (value) => {
            let digits = onlyDigits(value).slice(0, 11);
            if (digits.length > 9) {
                digits = digits.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (digits.length > 6) {
                digits = digits.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (digits.length > 3) {
                digits = digits.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            return digits;
        };

        const maskPhone = (value) => {
            const digits = onlyDigits(value).slice(0, 11);
            if (digits.length <= 10) {
                return digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }
            return digits
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        };

        const applyMask = (input) => {
            const type = input.getAttribute('data-mask');
            if (type === 'cpf') {
                input.value = maskCpf(input.value);
                input.addEventListener('input', () => {
                    input.value = maskCpf(input.value);
                });
            }
            if (type === 'phone') {
                input.value = maskPhone(input.value);
                input.addEventListener('input', () => {
                    input.value = maskPhone(input.value);
                });
            }
        };

        document.querySelectorAll('input[data-mask]').forEach(applyMask);
    });
</script>
