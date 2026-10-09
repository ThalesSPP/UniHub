const botaoTema = document.getElementById('botaoTema');
const iconeTema = document.getElementById('iconeTema');
const html = document.documentElement;

const temaSalvo = localStorage.getItem('tema');
const temaInicial = temaSalvo || html.getAttribute('data-bs-theme') || 'light';

html.setAttribute('data-bs-theme', temaInicial);

function atualizarBotaoTema(tema){
    if(!botaoTema){
        return;
    }

    const modoEscuro = tema === 'dark';

    if(iconeTema){
        iconeTema.textContent = modoEscuro ? '☀️' : '🌙';
    }

    const descricao = modoEscuro
        ? 'Ativar modo claro'
        : 'Ativar modo escuro';

    botaoTema.setAttribute('aria-label', descricao);
    botaoTema.setAttribute('title', descricao);
}

atualizarBotaoTema(temaInicial);

if(botaoTema){
    botaoTema.addEventListener('click', function(){
        const temaAtual = html.getAttribute('data-bs-theme');

        const novoTema = temaAtual === 'dark'
            ? 'light'
            : 'dark';

        html.setAttribute('data-bs-theme', novoTema);
        localStorage.setItem('tema', novoTema);

        atualizarBotaoTema(novoTema);
    });
}