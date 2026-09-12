const botaoTema = document.getElementById('botaoTema');
const html = document.documentElement;

const temaSalvo = localStorage.getItem('tema');

if (temaSalvo) {
    html.setAttribute('data-bs-theme', temaSalvo);

    if (botaoTema) {
        botaoTema.textContent = temaSalvo === 'dark' ? '☀️' : '🌙';
    }
}

if (botaoTema) {
    botaoTema.addEventListener('click', () => {
        const temaAtual = html.getAttribute('data-bs-theme');

        const novoTema =
            temaAtual === 'dark'
                ? 'light'
                : 'dark';

        html.setAttribute('data-bs-theme', novoTema);

        localStorage.setItem('tema', novoTema);

        botaoTema.textContent =
            novoTema === 'dark'
                ? '☀️'
                : '🌙';
    });
}