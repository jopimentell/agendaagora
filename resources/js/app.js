import './bootstrap';
import Alpine from 'alpinejs';

/* Tema claro/escuro (botão 🌓 do protótipo), lembrado no navegador */
window.toggleTheme = function () {
    const root = document.documentElement;
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    try { localStorage.setItem('agenda-tema', next); } catch (e) { /* sem armazenamento */ }
};

const MESES = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho',
    'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
const DIAS_SEMANA = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira',
    'Quinta-feira', 'Sexta-feira', 'Sábado'];

const pad = (n) => String(n).padStart(2, '0');

/* Tela 3 — calendário e horários (padrão Calendly) */
Alpine.data('agendamento', (config) => ({
    url: config.url,
    ano: config.ano,
    mes: config.mes, // 1-12
    dias: {},
    carregando: false,
    erro: null,
    dataSelecionada: config.dataInicial || null,
    horaSelecionada: config.horaInicial || null,
    etapa: config.dataInicial && config.horaInicial ? 'dados' : 'calendario',

    init() {
        if (this.dataSelecionada) {
            const [a, m] = this.dataSelecionada.split('-').map(Number);
            this.ano = a;
            this.mes = m;
        }
        this.carregar();
    },

    get tituloMes() {
        return `${MESES[this.mes - 1]} ${this.ano}`;
    },

    get celulas() {
        const primeiro = new Date(this.ano, this.mes - 1, 1).getDay();
        const total = new Date(this.ano, this.mes, 0).getDate();
        const celulas = [];
        for (let i = 0; i < primeiro; i++) celulas.push(null);
        for (let d = 1; d <= total; d++) {
            celulas.push({ dia: d, data: `${this.ano}-${pad(this.mes)}-${pad(d)}` });
        }
        return celulas;
    },

    get horarios() {
        return this.dataSelecionada ? (this.dias[this.dataSelecionada] || []) : [];
    },

    get rotuloDia() {
        if (!this.dataSelecionada) return '';
        const [a, m, d] = this.dataSelecionada.split('-').map(Number);
        return `${DIAS_SEMANA[new Date(a, m - 1, d).getDay()]}, ${d}`;
    },

    get resumo() {
        if (!this.dataSelecionada || !this.horaSelecionada) return '';
        const [a, m, d] = this.dataSelecionada.split('-');
        return `${this.rotuloDia.split(',')[0]}, ${d}/${m}/${a} às ${this.horaSelecionada}`;
    },

    disponivel(data) {
        return (this.dias[data] || []).length > 0;
    },

    async carregar() {
        this.carregando = true;
        this.erro = null;
        try {
            const resp = await fetch(`${this.url}?mes=${this.ano}-${pad(this.mes)}`, {
                // X-Requested-With: o Laravel não grava esta URL como "página anterior".
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!resp.ok) throw new Error();
            this.dias = (await resp.json()).dias || {};
        } catch (e) {
            this.erro = 'Não foi possível carregar os horários. Tente novamente.';
            this.dias = {};
        } finally {
            this.carregando = false;
        }
    },

    mudarMes(delta) {
        this.mes += delta;
        if (this.mes < 1) { this.mes = 12; this.ano--; }
        if (this.mes > 12) { this.mes = 1; this.ano++; }
        this.carregar();
    },

    escolherDia(celula) {
        if (!celula || !this.disponivel(celula.data)) return;
        this.dataSelecionada = celula.data;
        this.horaSelecionada = null;
    },

    escolherHora(hora) {
        this.horaSelecionada = hora;
    },

    continuar() {
        if (this.dataSelecionada && this.horaSelecionada) this.etapa = 'dados';
    },

    voltar() {
        this.etapa = 'calendario';
    },
}));

/* Botão "Copiar Link" do painel do administrador */
Alpine.data('copiarLink', (link) => ({
    copiado: false,
    async copiar() {
        try {
            await navigator.clipboard.writeText(link);
        } catch (e) {
            window.prompt('Copie o link:', link);
        }
        this.copiado = true;
        setTimeout(() => (this.copiado = false), 2000);
    },
}));

window.Alpine = Alpine;
Alpine.start();
