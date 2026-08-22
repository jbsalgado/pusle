--
-- PostgreSQL database dump
--

\restrict urkWm5N3NbtKz1yUh8PySpEdMpvo2hviGOSgk2Jqgx4WDkRe6SVuxKadDKJeDNv

-- Dumped from database version 18.4
-- Dumped by pg_dump version 18.4

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: delivery_admin_contas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_admin_contas (
    id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(150) NOT NULL,
    email character varying(100) NOT NULL,
    senha character varying(255) NOT NULL,
    is_superadmin boolean DEFAULT false,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE public.delivery_admin_contas OWNER TO postgres;

--
-- Name: delivery_admin_contas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_admin_contas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_admin_contas_id_seq OWNER TO postgres;

--
-- Name: delivery_admin_contas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_admin_contas_id_seq OWNED BY public.delivery_admin_contas.id;


--
-- Name: delivery_categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_categorias (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    imagem_url character varying(500),
    ordem_exibicao integer DEFAULT 0,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_categorias OWNER TO postgres;

--
-- Name: delivery_categorias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_categorias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_categorias_id_seq OWNER TO postgres;

--
-- Name: delivery_categorias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_categorias_id_seq OWNED BY public.delivery_categorias.id;


--
-- Name: delivery_clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_clientes (
    id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    tipo_pessoa_id integer NOT NULL,
    nome character varying(150) NOT NULL,
    email character varying(100),
    telefone character varying(20) NOT NULL,
    cpf_cnpj character varying(18),
    data_nascimento date,
    aceita_marketing boolean DEFAULT true,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE public.delivery_clientes OWNER TO postgres;

--
-- Name: delivery_clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_clientes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_clientes_id_seq OWNER TO postgres;

--
-- Name: delivery_clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_clientes_id_seq OWNED BY public.delivery_clientes.id;


--
-- Name: delivery_complementos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_complementos (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    preco numeric(8,2) DEFAULT 0.00 NOT NULL,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT delivery_complementos_preco_check CHECK ((preco >= (0)::numeric))
);


ALTER TABLE public.delivery_complementos OWNER TO postgres;

--
-- Name: delivery_complementos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_complementos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_complementos_id_seq OWNER TO postgres;

--
-- Name: delivery_complementos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_complementos_id_seq OWNED BY public.delivery_complementos.id;


--
-- Name: delivery_configuracoes_estabelecimento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_configuracoes_estabelecimento (
    id integer NOT NULL,
    estabelecimento_id integer CONSTRAINT delivery_configuracoes_estabelecime_estabelecimento_id_not_null NOT NULL,
    aceita_pedidos_online boolean DEFAULT true,
    pedido_minimo_delivery numeric(8,2) DEFAULT 0.00,
    pedido_minimo_retirada numeric(8,2) DEFAULT 0.00,
    tempo_preparo_padrao integer DEFAULT 30,
    taxa_entrega_fixa numeric(8,2) DEFAULT 0.00,
    entrega_gratis_acima_de numeric(8,2),
    raio_entrega_maximo numeric(5,2) DEFAULT 10.00,
    aceita_dinheiro boolean DEFAULT true,
    aceita_cartao boolean DEFAULT true,
    aceita_pix boolean DEFAULT true,
    valor_minimo_cartao numeric(8,2) DEFAULT 0.00,
    cor_primaria character varying(7) DEFAULT '#FF6B35'::character varying,
    cor_secundaria character varying(7) DEFAULT '#2E8B57'::character varying,
    logo_url character varying(500),
    banner_url character varying(500),
    notificar_pedido_whatsapp boolean DEFAULT true,
    notificar_pedido_email boolean DEFAULT true,
    telefone_notificacao character varying(20),
    permite_agendamento boolean DEFAULT false,
    antecedencia_agendamento_horas integer DEFAULT 2,
    limite_pedidos_simultaneos integer DEFAULT 50,
    integrar_ifood boolean DEFAULT false,
    integrar_ubereats boolean DEFAULT false,
    integrar_rappi boolean DEFAULT false,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_configuracoes_estabelecimento OWNER TO postgres;

--
-- Name: delivery_configuracoes_estabelecimento_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_configuracoes_estabelecimento_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_configuracoes_estabelecimento_id_seq OWNER TO postgres;

--
-- Name: delivery_configuracoes_estabelecimento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_configuracoes_estabelecimento_id_seq OWNED BY public.delivery_configuracoes_estabelecimento.id;


--
-- Name: delivery_enderecos_cliente; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_enderecos_cliente (
    id integer NOT NULL,
    cliente_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome_endereco character varying(50) NOT NULL,
    cep character varying(10),
    logradouro character varying(200) NOT NULL,
    numero character varying(20) NOT NULL,
    complemento character varying(100),
    bairro character varying(100) NOT NULL,
    cidade character varying(100) NOT NULL,
    uf character varying(2) NOT NULL,
    referencia text,
    latitude numeric(10,8),
    longitude numeric(11,8),
    padrao boolean DEFAULT false,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_enderecos_cliente OWNER TO postgres;

--
-- Name: delivery_enderecos_cliente_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_enderecos_cliente_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_enderecos_cliente_id_seq OWNER TO postgres;

--
-- Name: delivery_enderecos_cliente_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_enderecos_cliente_id_seq OWNED BY public.delivery_enderecos_cliente.id;


--
-- Name: delivery_entregadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_entregadores (
    id integer NOT NULL,
    estabelecimento_id integer,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(100) NOT NULL,
    email character varying(100),
    telefone character varying(20) NOT NULL,
    cpf character varying(14) NOT NULL,
    data_nascimento date,
    cnh character varying(20),
    tipo_veiculo character varying(50),
    placa_veiculo character varying(10),
    disponivel boolean DEFAULT false,
    aceita_pedidos boolean DEFAULT true,
    raio_atuacao_km numeric(5,2) DEFAULT 10.00,
    latitude_atual numeric(10,8),
    longitude_atual numeric(11,8),
    ultima_atualizacao_localizacao timestamp with time zone,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE public.delivery_entregadores OWNER TO postgres;

--
-- Name: delivery_entregadores_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_entregadores_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_entregadores_id_seq OWNER TO postgres;

--
-- Name: delivery_entregadores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_entregadores_id_seq OWNED BY public.delivery_entregadores.id;


--
-- Name: delivery_entregas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_entregas (
    id integer NOT NULL,
    pedido_id integer NOT NULL,
    entregador_id integer,
    endereco_entrega jsonb NOT NULL,
    data_atribuicao timestamp with time zone DEFAULT now(),
    data_aceite timestamp with time zone,
    data_saida timestamp with time zone,
    data_chegada timestamp with time zone,
    data_finalizacao timestamp with time zone,
    observacoes text,
    motivo_cancelamento text,
    coordenadas_entregador jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_entregas OWNER TO postgres;

--
-- Name: delivery_entregas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_entregas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_entregas_id_seq OWNER TO postgres;

--
-- Name: delivery_entregas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_entregas_id_seq OWNED BY public.delivery_entregas.id;


--
-- Name: delivery_estabelecimentos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_estabelecimentos (
    id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome_fantasia character varying(150) NOT NULL,
    razao_social character varying(150),
    cnpj character varying(18),
    email_principal character varying(100) NOT NULL,
    senha character varying(255) NOT NULL,
    telefone character varying(20),
    whatsapp character varying(20),
    site character varying(255),
    cep character varying(10),
    logradouro character varying(200),
    numero character varying(20),
    complemento character varying(100),
    bairro character varying(100),
    cidade character varying(100),
    uf character varying(2),
    latitude numeric(10,8),
    longitude numeric(11,8),
    taxa_entrega_padrao numeric(8,2) DEFAULT 0.00,
    tempo_preparo_medio integer DEFAULT 30,
    pedido_minimo numeric(8,2) DEFAULT 0.00,
    raio_entrega_km numeric(5,2) DEFAULT 5.00,
    horarios_funcionamento jsonb,
    ativo boolean DEFAULT true,
    aprovado boolean DEFAULT false,
    data_cadastro timestamp with time zone DEFAULT now() NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone
);


ALTER TABLE public.delivery_estabelecimentos OWNER TO postgres;

--
-- Name: delivery_estabelecimentos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_estabelecimentos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_estabelecimentos_id_seq OWNER TO postgres;

--
-- Name: delivery_estabelecimentos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_estabelecimentos_id_seq OWNED BY public.delivery_estabelecimentos.id;


--
-- Name: delivery_movimentacoes_financeiras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_movimentacoes_financeiras (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    pedido_id integer,
    status_id integer NOT NULL,
    tipo character varying(30) NOT NULL,
    categoria character varying(50),
    valor numeric(12,2) NOT NULL,
    valor_liquido numeric(12,2),
    descricao text NOT NULL,
    documento character varying(100),
    data_movimento date DEFAULT CURRENT_DATE NOT NULL,
    data_vencimento date,
    data_pagamento date,
    gateway_pagamento character varying(50),
    transacao_id character varying(100),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT delivery_movimentacoes_financeiras_valor_check CHECK ((valor <> (0)::numeric))
);


ALTER TABLE public.delivery_movimentacoes_financeiras OWNER TO postgres;

--
-- Name: delivery_movimentacoes_financeiras_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_movimentacoes_financeiras_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_movimentacoes_financeiras_id_seq OWNER TO postgres;

--
-- Name: delivery_movimentacoes_financeiras_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_movimentacoes_financeiras_id_seq OWNED BY public.delivery_movimentacoes_financeiras.id;


--
-- Name: delivery_pedido_complementos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_pedido_complementos (
    id integer NOT NULL,
    pedido_item_id integer NOT NULL,
    complemento_id integer NOT NULL,
    quantidade integer DEFAULT 1 NOT NULL,
    preco_unitario numeric(8,2) NOT NULL,
    preco_total numeric(10,2) NOT NULL,
    CONSTRAINT delivery_pedido_complementos_quantidade_check CHECK ((quantidade > 0))
);


ALTER TABLE public.delivery_pedido_complementos OWNER TO postgres;

--
-- Name: delivery_pedido_complementos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_pedido_complementos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_pedido_complementos_id_seq OWNER TO postgres;

--
-- Name: delivery_pedido_complementos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_pedido_complementos_id_seq OWNED BY public.delivery_pedido_complementos.id;


--
-- Name: delivery_pedido_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_pedido_itens (
    id integer NOT NULL,
    pedido_id integer NOT NULL,
    produto_id integer NOT NULL,
    variacao_id integer,
    quantidade integer DEFAULT 1 NOT NULL,
    preco_unitario numeric(10,2) NOT NULL,
    preco_total numeric(12,2) NOT NULL,
    observacoes text,
    CONSTRAINT delivery_pedido_itens_preco_unitario_check CHECK ((preco_unitario > (0)::numeric)),
    CONSTRAINT delivery_pedido_itens_quantidade_check CHECK ((quantidade > 0))
);


ALTER TABLE public.delivery_pedido_itens OWNER TO postgres;

--
-- Name: delivery_pedido_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_pedido_itens_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_pedido_itens_id_seq OWNER TO postgres;

--
-- Name: delivery_pedido_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_pedido_itens_id_seq OWNED BY public.delivery_pedido_itens.id;


--
-- Name: delivery_pedidos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_pedidos (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    cliente_id integer NOT NULL,
    endereco_cliente_id integer,
    status_id integer NOT NULL,
    tipo_entrega_id integer NOT NULL,
    tipo_pagamento_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    numero_pedido character varying(20) NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    taxa_entrega numeric(8,2) DEFAULT 0.00,
    desconto numeric(8,2) DEFAULT 0.00,
    total numeric(12,2) NOT NULL,
    valor_pago numeric(12,2),
    troco_para numeric(8,2),
    troco numeric(8,2),
    observacoes text,
    observacoes_internas text,
    tempo_estimado_preparo integer,
    tempo_estimado_entrega integer,
    tempo_real_preparo integer,
    tempo_real_entrega integer,
    data_pedido timestamp with time zone DEFAULT now() NOT NULL,
    data_confirmacao timestamp with time zone,
    data_preparo_inicio timestamp with time zone,
    data_preparo_fim timestamp with time zone,
    data_saiu_entrega timestamp with time zone,
    data_entrega timestamp with time zone,
    data_cancelamento timestamp with time zone,
    nota_avaliacao integer,
    comentario_avaliacao text,
    data_avaliacao timestamp with time zone,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT delivery_pedidos_nota_avaliacao_check CHECK (((nota_avaliacao >= 1) AND (nota_avaliacao <= 5)))
);


ALTER TABLE public.delivery_pedidos OWNER TO postgres;

--
-- Name: delivery_pedidos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_pedidos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_pedidos_id_seq OWNER TO postgres;

--
-- Name: delivery_pedidos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_pedidos_id_seq OWNED BY public.delivery_pedidos.id;


--
-- Name: delivery_produto_complementos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_produto_complementos (
    id integer NOT NULL,
    produto_id integer NOT NULL,
    complemento_id integer NOT NULL,
    obrigatorio boolean DEFAULT false,
    quantidade_maxima integer DEFAULT 1
);


ALTER TABLE public.delivery_produto_complementos OWNER TO postgres;

--
-- Name: delivery_produto_complementos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_produto_complementos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_produto_complementos_id_seq OWNER TO postgres;

--
-- Name: delivery_produto_complementos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_produto_complementos_id_seq OWNED BY public.delivery_produto_complementos.id;


--
-- Name: delivery_produtos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_produtos (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    categoria_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(150) NOT NULL,
    descricao text,
    preco numeric(10,2) NOT NULL,
    preco_promocional numeric(10,2),
    imagem_url character varying(500),
    imagens_extras jsonb,
    tempo_preparo_minutos integer DEFAULT 15,
    serve_quantas_pessoas integer DEFAULT 1,
    peso_gramas integer,
    calorias integer,
    disponivel boolean DEFAULT true,
    destaque boolean DEFAULT false,
    ordem_exibicao integer DEFAULT 0,
    disponivel_seg boolean DEFAULT true,
    disponivel_ter boolean DEFAULT true,
    disponivel_qua boolean DEFAULT true,
    disponivel_qui boolean DEFAULT true,
    disponivel_sex boolean DEFAULT true,
    disponivel_sab boolean DEFAULT true,
    disponivel_dom boolean DEFAULT true,
    horario_inicio time without time zone,
    horario_fim time without time zone,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    deleted_at timestamp with time zone,
    CONSTRAINT delivery_produtos_check CHECK (((preco_promocional IS NULL) OR (preco_promocional < preco))),
    CONSTRAINT delivery_produtos_preco_check CHECK ((preco > (0)::numeric))
);


ALTER TABLE public.delivery_produtos OWNER TO postgres;

--
-- Name: delivery_produtos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_produtos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_produtos_id_seq OWNER TO postgres;

--
-- Name: delivery_produtos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_produtos_id_seq OWNED BY public.delivery_produtos.id;


--
-- Name: delivery_promocoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_promocoes (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    codigo_cupom character varying(20),
    tipo_desconto character varying(20) NOT NULL,
    valor_desconto numeric(8,2),
    percentual_desconto numeric(5,2),
    valor_minimo_pedido numeric(8,2) DEFAULT 0.00,
    quantidade_maxima_uso integer,
    uso_por_cliente integer DEFAULT 1,
    aplica_produtos boolean DEFAULT true,
    aplica_frete boolean DEFAULT false,
    produtos_incluidos jsonb,
    produtos_excluidos jsonb,
    data_inicio date NOT NULL,
    data_fim date NOT NULL,
    dias_semana_validos jsonb,
    horario_inicio time without time zone,
    horario_fim time without time zone,
    ativo boolean DEFAULT true,
    quantidade_usada integer DEFAULT 0,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_promocoes OWNER TO postgres;

--
-- Name: delivery_promocoes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_promocoes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_promocoes_id_seq OWNER TO postgres;

--
-- Name: delivery_promocoes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_promocoes_id_seq OWNED BY public.delivery_promocoes.id;


--
-- Name: delivery_status_financeiro; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_status_financeiro (
    id integer NOT NULL,
    codigo character varying(30) NOT NULL,
    descricao character varying(50) NOT NULL,
    ativo boolean DEFAULT true
);


ALTER TABLE public.delivery_status_financeiro OWNER TO postgres;

--
-- Name: delivery_status_financeiro_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_status_financeiro_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_status_financeiro_id_seq OWNER TO postgres;

--
-- Name: delivery_status_financeiro_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_status_financeiro_id_seq OWNED BY public.delivery_status_financeiro.id;


--
-- Name: delivery_status_pedido; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_status_pedido (
    id integer NOT NULL,
    codigo character varying(30) NOT NULL,
    descricao character varying(50) NOT NULL,
    ordem_exibicao integer NOT NULL,
    cor_hex character varying(7),
    ativo boolean DEFAULT true
);


ALTER TABLE public.delivery_status_pedido OWNER TO postgres;

--
-- Name: delivery_status_pedido_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_status_pedido_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_status_pedido_id_seq OWNER TO postgres;

--
-- Name: delivery_status_pedido_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_status_pedido_id_seq OWNED BY public.delivery_status_pedido.id;


--
-- Name: delivery_tipos_entrega; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_tipos_entrega (
    id integer NOT NULL,
    codigo character varying(30) NOT NULL,
    descricao character varying(50) NOT NULL,
    ativo boolean DEFAULT true
);


ALTER TABLE public.delivery_tipos_entrega OWNER TO postgres;

--
-- Name: delivery_tipos_entrega_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_tipos_entrega_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_tipos_entrega_id_seq OWNER TO postgres;

--
-- Name: delivery_tipos_entrega_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_tipos_entrega_id_seq OWNED BY public.delivery_tipos_entrega.id;


--
-- Name: delivery_tipos_pagamento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_tipos_pagamento (
    id integer NOT NULL,
    codigo character varying(30) NOT NULL,
    descricao character varying(50) NOT NULL,
    requer_troco boolean DEFAULT false,
    taxa_percentual numeric(5,2) DEFAULT 0.00,
    ativo boolean DEFAULT true
);


ALTER TABLE public.delivery_tipos_pagamento OWNER TO postgres;

--
-- Name: delivery_tipos_pagamento_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_tipos_pagamento_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_tipos_pagamento_id_seq OWNER TO postgres;

--
-- Name: delivery_tipos_pagamento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_tipos_pagamento_id_seq OWNED BY public.delivery_tipos_pagamento.id;


--
-- Name: delivery_tipos_pessoa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_tipos_pessoa (
    id integer NOT NULL,
    codigo character varying(20) NOT NULL,
    descricao character varying(50) NOT NULL,
    ativo boolean DEFAULT true
);


ALTER TABLE public.delivery_tipos_pessoa OWNER TO postgres;

--
-- Name: delivery_tipos_pessoa_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_tipos_pessoa_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_tipos_pessoa_id_seq OWNER TO postgres;

--
-- Name: delivery_tipos_pessoa_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_tipos_pessoa_id_seq OWNED BY public.delivery_tipos_pessoa.id;


--
-- Name: delivery_uso_promocoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_uso_promocoes (
    id integer NOT NULL,
    promocao_id integer NOT NULL,
    pedido_id integer NOT NULL,
    cliente_id integer NOT NULL,
    valor_desconto_aplicado numeric(8,2) NOT NULL,
    data_uso timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_uso_promocoes OWNER TO postgres;

--
-- Name: delivery_uso_promocoes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_uso_promocoes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_uso_promocoes_id_seq OWNER TO postgres;

--
-- Name: delivery_uso_promocoes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_uso_promocoes_id_seq OWNED BY public.delivery_uso_promocoes.id;


--
-- Name: delivery_usuarios_estabelecimento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_usuarios_estabelecimento (
    id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    uuid uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    senha character varying(255) NOT NULL,
    telefone character varying(20),
    role character varying(20) DEFAULT 'OPERADOR'::character varying NOT NULL,
    ativo boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.delivery_usuarios_estabelecimento OWNER TO postgres;

--
-- Name: delivery_usuarios_estabelecimento_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_usuarios_estabelecimento_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_usuarios_estabelecimento_id_seq OWNER TO postgres;

--
-- Name: delivery_usuarios_estabelecimento_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_usuarios_estabelecimento_id_seq OWNED BY public.delivery_usuarios_estabelecimento.id;


--
-- Name: delivery_v_produtos_mais_vendidos; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.delivery_v_produtos_mais_vendidos AS
 SELECT pi.produto_id,
    p.estabelecimento_id,
    pr.nome AS produto_nome,
    sum(pi.quantidade) AS quantidade_vendida,
    sum(pi.preco_total) AS valor_total_vendas,
    count(DISTINCT pi.pedido_id) AS pedidos_distintos
   FROM ((public.delivery_pedido_itens pi
     JOIN public.delivery_pedidos p ON ((pi.pedido_id = p.id)))
     JOIN public.delivery_produtos pr ON ((pi.produto_id = pr.id)))
  WHERE (p.status_id IN ( SELECT delivery_status_pedido.id
           FROM public.delivery_status_pedido
          WHERE ((delivery_status_pedido.codigo)::text = ANY (ARRAY[('ENTREGUE'::character varying)::text, ('CONCLUIDO'::character varying)::text]))))
  GROUP BY pi.produto_id, p.estabelecimento_id, pr.nome;


ALTER VIEW public.delivery_v_produtos_mais_vendidos OWNER TO postgres;

--
-- Name: delivery_v_vendas_diarias; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.delivery_v_vendas_diarias AS
 SELECT estabelecimento_id,
    date(data_pedido) AS data_venda,
    count(*) AS quantidade_pedidos,
    sum(total) AS valor_total,
    avg(total) AS ticket_medio,
    count(
        CASE
            WHEN (status_id IN ( SELECT delivery_status_pedido.id
               FROM public.delivery_status_pedido
              WHERE ((delivery_status_pedido.codigo)::text = ANY (ARRAY[('ENTREGUE'::character varying)::text, ('CONCLUIDO'::character varying)::text])))) THEN 1
            ELSE NULL::integer
        END) AS pedidos_concluidos
   FROM public.delivery_pedidos p
  GROUP BY estabelecimento_id, (date(data_pedido));


ALTER VIEW public.delivery_v_vendas_diarias OWNER TO postgres;

--
-- Name: delivery_variacoes_produto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.delivery_variacoes_produto (
    id integer NOT NULL,
    produto_id integer NOT NULL,
    estabelecimento_id integer NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    preco_adicional numeric(8,2) DEFAULT 0.00,
    ordem_exibicao integer DEFAULT 0,
    ativo boolean DEFAULT true
);


ALTER TABLE public.delivery_variacoes_produto OWNER TO postgres;

--
-- Name: delivery_variacoes_produto_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.delivery_variacoes_produto_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.delivery_variacoes_produto_id_seq OWNER TO postgres;

--
-- Name: delivery_variacoes_produto_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.delivery_variacoes_produto_id_seq OWNED BY public.delivery_variacoes_produto.id;


--
-- Name: prest_cobranca_configuracao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_cobranca_configuracao (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    ativo boolean DEFAULT true,
    whatsapp_provider character varying(20) DEFAULT 'zapi'::character varying,
    zapi_instance_id character varying(100),
    zapi_token character varying(255),
    dias_antes_vencimento integer DEFAULT 3,
    enviar_dia_vencimento boolean DEFAULT true,
    dias_apos_vencimento integer DEFAULT 1,
    horario_envio time without time zone DEFAULT '09:00:00'::time without time zone,
    data_criacao timestamp with time zone DEFAULT now(),
    data_atualizacao timestamp with time zone DEFAULT now()
);


ALTER TABLE public.prest_cobranca_configuracao OWNER TO postgres;

--
-- Name: TABLE prest_cobranca_configuracao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_cobranca_configuracao IS 'Configurações de automação de cobranças por usuário';


--
-- Name: COLUMN prest_cobranca_configuracao.ativo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_configuracao.ativo IS 'Define se a automação está ativa';


--
-- Name: COLUMN prest_cobranca_configuracao.dias_antes_vencimento; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_configuracao.dias_antes_vencimento IS 'Quantos dias antes do vencimento enviar lembrete';


--
-- Name: COLUMN prest_cobranca_configuracao.horario_envio; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_configuracao.horario_envio IS 'Horário padrão para envio das mensagens';


--
-- Name: prest_cobranca_historico; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_cobranca_historico (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    parcela_id uuid NOT NULL,
    tipo character varying(20) NOT NULL,
    telefone character varying(20) NOT NULL,
    mensagem text NOT NULL,
    status character varying(20) DEFAULT 'PENDENTE'::character varying,
    resposta_api text,
    tentativas integer DEFAULT 0,
    data_envio timestamp with time zone,
    data_criacao timestamp with time zone DEFAULT now(),
    CONSTRAINT chk_cobranca_historico_status CHECK (((status)::text = ANY (ARRAY[('ENVIADO'::character varying)::text, ('FALHA'::character varying)::text, ('PENDENTE'::character varying)::text]))),
    CONSTRAINT chk_cobranca_historico_tipo CHECK (((tipo)::text = ANY (ARRAY[('ANTES'::character varying)::text, ('DIA'::character varying)::text, ('APOS'::character varying)::text])))
);


ALTER TABLE public.prest_cobranca_historico OWNER TO postgres;

--
-- Name: TABLE prest_cobranca_historico; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_cobranca_historico IS 'Histórico de envios de cobranças via WhatsApp';


--
-- Name: COLUMN prest_cobranca_historico.status; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_historico.status IS 'Status do envio: ENVIADO (sucesso), FALHA (erro), PENDENTE (aguardando)';


--
-- Name: COLUMN prest_cobranca_historico.resposta_api; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_historico.resposta_api IS 'Resposta JSON da API do WhatsApp';


--
-- Name: COLUMN prest_cobranca_historico.tentativas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_historico.tentativas IS 'Número de tentativas de envio';


--
-- Name: prest_cobranca_template; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_cobranca_template (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    tipo character varying(20) NOT NULL,
    titulo character varying(100) NOT NULL,
    mensagem text NOT NULL,
    ativo boolean DEFAULT true,
    data_criacao timestamp with time zone DEFAULT now(),
    data_atualizacao timestamp with time zone DEFAULT now(),
    CONSTRAINT chk_cobranca_template_tipo CHECK (((tipo)::text = ANY (ARRAY[('ANTES'::character varying)::text, ('DIA'::character varying)::text, ('APOS'::character varying)::text])))
);


ALTER TABLE public.prest_cobranca_template OWNER TO postgres;

--
-- Name: TABLE prest_cobranca_template; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_cobranca_template IS 'Templates de mensagens para cobranças';


--
-- Name: COLUMN prest_cobranca_template.tipo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_template.tipo IS 'Tipo de template: ANTES (antes do vencimento), DIA (dia do vencimento), APOS (após vencimento)';


--
-- Name: COLUMN prest_cobranca_template.mensagem; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_cobranca_template.mensagem IS 'Texto do template com variáveis: {nome}, {valor}, {vencimento}, {parcela}';


--
-- Name: prest_configuracoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_configuracoes (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome_loja character varying(150),
    logo_path character varying(500),
    cor_primaria character varying(7) DEFAULT '#3B82F6'::character varying NOT NULL,
    cor_secundaria character varying(7) DEFAULT '#10B981'::character varying NOT NULL,
    catalogo_publico boolean DEFAULT false NOT NULL,
    aceita_orcamentos boolean DEFAULT true NOT NULL,
    whatsapp character varying(20),
    instagram character varying(100),
    facebook character varying(100),
    endereco_completo text,
    mensagem_boas_vindas text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    pix_chave character varying(100),
    pix_nome character varying(100),
    pix_cidade character varying(50),
    imprimir_automatico boolean DEFAULT false,
    certificado_pfx text,
    certificado_senha character varying(255),
    cnpj character varying(255),
    crt integer,
    ie character varying(255),
    nfce_csc character varying(255),
    nfce_csc_id character varying(255),
    nfe_ambiente integer,
    razao_social character varying(255)
);


ALTER TABLE public.prest_configuracoes OWNER TO postgres;

--
-- Name: COLUMN prest_configuracoes.pix_chave; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_configuracoes.pix_chave IS 'Chave PIX (celular, CPF, CNPJ, email ou chave aleatória)';


--
-- Name: COLUMN prest_configuracoes.pix_nome; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_configuracoes.pix_nome IS 'Nome do recebedor para QR Code PIX (máx 25 caracteres, sem acentos)';


--
-- Name: COLUMN prest_configuracoes.pix_cidade; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_configuracoes.pix_cidade IS 'Cidade do recebedor para QR Code PIX (máx 15 caracteres, sem acentos)';


--
-- Name: prest_contas_pagar; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_contas_pagar (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    fornecedor_id uuid,
    compra_id uuid,
    descricao character varying(255) NOT NULL,
    valor numeric(10,2) NOT NULL,
    data_vencimento date NOT NULL,
    data_pagamento date,
    status character varying(20) DEFAULT 'PENDENTE'::character varying NOT NULL,
    forma_pagamento_id uuid,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    arquivo_comprovante character varying(255),
    tipo_despesa_id uuid,
    CONSTRAINT prest_contas_pagar_status_check CHECK (((status)::text = ANY (ARRAY[('PENDENTE'::character varying)::text, ('PAGA'::character varying)::text, ('VENCIDA'::character varying)::text, ('CANCELADA'::character varying)::text]))),
    CONSTRAINT prest_contas_pagar_valor_check CHECK ((valor > (0)::numeric))
);


ALTER TABLE public.prest_contas_pagar OWNER TO postgres;

--
-- Name: TABLE prest_contas_pagar; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_contas_pagar IS 'Registra contas a pagar da empresa';


--
-- Name: COLUMN prest_contas_pagar.fornecedor_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_contas_pagar.fornecedor_id IS 'Fornecedor relacionado (opcional)';


--
-- Name: COLUMN prest_contas_pagar.compra_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_contas_pagar.compra_id IS 'Compra relacionada (se a conta foi gerada a partir de uma compra)';


--
-- Name: COLUMN prest_contas_pagar.status; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_contas_pagar.status IS 'PENDENTE, PAGA, VENCIDA ou CANCELADA';


--
-- Name: prest_estoque_movimentacoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_estoque_movimentacoes (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    produto_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    tipo_movimentacao character varying(20) NOT NULL,
    quantidade integer NOT NULL,
    saldo_anterior integer NOT NULL,
    saldo_novo integer NOT NULL,
    venda_id uuid,
    observacao text,
    data_movimentacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_estoque_movimentacoes OWNER TO postgres;

--
-- Name: prest_formas_pagamento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_formas_pagamento (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(100) NOT NULL,
    tipo character varying(20) NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    aceita_parcelamento boolean DEFAULT false NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_formas_pagamento OWNER TO postgres;

--
-- Name: prest_historico_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_historico_cobranca (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    parcela_id uuid NOT NULL,
    cobrador_id uuid NOT NULL,
    cliente_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    tipo_acao character varying(20) NOT NULL,
    valor_recebido numeric(10,2),
    observacao text,
    localizacao_lat numeric(10,6),
    localizacao_lng numeric(10,6),
    data_acao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_historico_cobranca OWNER TO postgres;

--
-- Name: prest_orcamento_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_orcamento_itens (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    orcamento_id uuid NOT NULL,
    produto_id uuid NOT NULL,
    quantidade integer NOT NULL,
    preco_unitario numeric(10,2) NOT NULL,
    valor_total numeric(10,2) NOT NULL
);


ALTER TABLE public.prest_orcamento_itens OWNER TO postgres;

--
-- Name: prest_orcamentos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_orcamentos (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    cliente_id uuid,
    nome_cliente character varying(150),
    telefone_cliente character varying(20),
    email_cliente character varying(100),
    valor_total numeric(10,2) NOT NULL,
    status character varying(20) DEFAULT 'PENDENTE'::character varying NOT NULL,
    venda_id uuid,
    validade_ate date,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_orcamentos OWNER TO postgres;

--
-- Name: prest_periodos_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_periodos_cobranca (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    mes_referencia integer NOT NULL,
    ano_referencia integer NOT NULL,
    descricao character varying(100),
    data_inicio date NOT NULL,
    data_fim date NOT NULL,
    status character varying(20) DEFAULT 'ABERTO'::character varying NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_periodos_cobranca OWNER TO postgres;

--
-- Name: prest_regioes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_regioes (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    cor_identificacao character varying(7),
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_regioes OWNER TO postgres;

--
-- Name: prest_rotas_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_rotas_cobranca (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    periodo_id uuid NOT NULL,
    cobrador_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    dia_semana integer,
    nome_rota character varying(100) NOT NULL,
    descricao text,
    ordem_execucao integer DEFAULT 0 NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_rotas_cobranca OWNER TO postgres;

--
-- Name: prest_taxas_entrega; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_taxas_entrega (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    cidade character varying(100),
    bairro character varying(100),
    cep character varying(10),
    valor numeric(10,2) DEFAULT 0.00 NOT NULL,
    ativo boolean DEFAULT true,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    valor_minimo_frete_gratis numeric(10,2) DEFAULT NULL::numeric,
    observacoes text,
    porte character varying(1) DEFAULT 'P'::character varying
);


ALTER TABLE public.prest_taxas_entrega OWNER TO postgres;

--
-- Name: prest_tipos_despesa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_tipos_despesa (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(100) NOT NULL,
    grupo character varying(30) NOT NULL,
    descricao text,
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT prest_tipos_despesa_grupo_check CHECK (((grupo)::text = ANY (ARRAY[('FIXA'::character varying)::text, ('VARIAVEL'::character varying)::text, ('MERCADORIA'::character varying)::text])))
);


ALTER TABLE public.prest_tipos_despesa OWNER TO postgres;

--
-- Name: prest_vendedores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_vendedores (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome_completo character varying(150) NOT NULL,
    cpf character varying(11),
    telefone character varying(20),
    email character varying(100),
    percentual_comissao numeric(5,2) DEFAULT 0.00 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    data_admissao date,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_vendedores OWNER TO postgres;

--
-- Name: vw_clientes_cobrador; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_clientes_cobrador AS
 SELECT cc.cobrador_id,
    cc.periodo_id,
    c.id,
    c.usuario_id,
    c.nome_completo,
    c.cpf,
    c.telefone,
    c.email,
    c.endereco_logradouro,
    c.endereco_numero,
    c.endereco_complemento,
    c.endereco_bairro,
    c.endereco_cidade,
    c.endereco_estado,
    c.endereco_cep,
    c.ponto_referencia,
    c.observacoes,
    c.ativo,
    c.data_criacao,
    c.data_atualizacao,
    c.regiao_id,
    cc.rota_id,
    cc.total_parcelas,
    cc.parcelas_pagas,
    cc.valor_total,
    cc.valor_recebido,
    concat_ws(', '::text, concat_ws(' '::text, c.endereco_logradouro, c.endereco_numero), c.endereco_complemento, c.endereco_bairro, c.endereco_cidade, c.endereco_estado, c.endereco_cep) AS endereco_completo,
        CASE
            WHEN (cc.parcelas_pagas >= cc.total_parcelas) THEN 'QUITADO'::text
            WHEN (cc.parcelas_pagas = 0) THEN 'PENDENTE'::text
            ELSE 'PARCIAL'::text
        END AS status_cobranca
   FROM (public.prest_carteira_cobranca cc
     JOIN public.prest_clientes c ON ((c.id = cc.cliente_id)))
  WHERE (cc.ativo = true);


ALTER VIEW public.vw_clientes_cobrador OWNER TO postgres;

--
-- Name: vw_dashboard_cobrador; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_dashboard_cobrador AS
 SELECT cc.cobrador_id,
    cc.periodo_id,
    col.nome_completo AS cobrador_nome,
    p.mes_referencia,
    p.ano_referencia,
    count(DISTINCT cc.cliente_id) AS total_clientes,
    count(DISTINCT
        CASE
            WHEN (cc.parcelas_pagas >= cc.total_parcelas) THEN cc.cliente_id
            ELSE NULL::uuid
        END) AS clientes_quitados,
    sum(cc.total_parcelas) AS total_parcelas,
    sum(cc.parcelas_pagas) AS parcelas_pagas,
    sum(cc.valor_total) AS valor_total,
    sum(cc.valor_recebido) AS valor_recebido,
    sum((cc.valor_total - cc.valor_recebido)) AS saldo_pendente
   FROM ((public.prest_carteira_cobranca cc
     JOIN public.prest_colaboradores col ON ((col.id = cc.cobrador_id)))
     JOIN public.prest_periodos_cobranca p ON ((p.id = cc.periodo_id)))
  WHERE (cc.ativo = true)
  GROUP BY cc.cobrador_id, cc.periodo_id, col.nome_completo, p.mes_referencia, p.ano_referencia;


ALTER VIEW public.vw_dashboard_cobrador OWNER TO postgres;

--
-- Name: vw_historico_compras_produto; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_historico_compras_produto AS
 SELECT ic.produto_id,
    p.nome AS nome_produto,
    ic.compra_id,
    c.data_compra,
    c.fornecedor_id,
    f.nome_fantasia AS nome_fornecedor,
    ic.preco_unitario,
    ic.quantidade,
    ic.valor_total_item,
    c.numero_nota_fiscal,
    c.status_compra,
    row_number() OVER (PARTITION BY ic.produto_id, c.fornecedor_id ORDER BY c.data_compra DESC) AS ordem_compra_fornecedor,
    row_number() OVER (PARTITION BY ic.produto_id ORDER BY c.data_compra DESC) AS ordem_compra_geral
   FROM (((public.prest_itens_compra ic
     JOIN public.prest_compras c ON ((ic.compra_id = c.id)))
     JOIN public.prest_produtos p ON ((ic.produto_id = p.id)))
     JOIN public.prest_fornecedores f ON ((c.fornecedor_id = f.id)))
  WHERE ((c.status_compra)::text <> 'CANCELADA'::text)
  ORDER BY ic.produto_id, c.data_compra DESC;


ALTER VIEW public.vw_historico_compras_produto OWNER TO postgres;

--
-- Name: VIEW vw_historico_compras_produto; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON VIEW public.vw_historico_compras_produto IS 'View para consultar histórico de compras por produto, incluindo fornecedor e preços';


--
-- Name: vw_parcelas_cobrador; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_parcelas_cobrador AS
 SELECT p.id,
    p.venda_id,
    p.usuario_id,
    p.numero_parcela,
    p.valor_parcela,
    p.data_vencimento,
    p.status_parcela_codigo,
    p.data_pagamento,
    p.valor_pago,
    p.observacoes,
    p.forma_pagamento_id,
    p.cobrador_id,
    p.carteira_cobranca_id,
    v.cliente_id,
    c.nome_completo AS cliente_nome,
    c.telefone AS cliente_telefone,
    c.endereco_logradouro,
    c.endereco_numero,
    c.endereco_complemento,
    c.endereco_bairro,
    c.endereco_cidade,
    c.endereco_estado,
    c.endereco_cep,
    c.ponto_referencia,
    concat_ws(', '::text, concat_ws(' '::text, c.endereco_logradouro, c.endereco_numero), c.endereco_complemento, c.endereco_bairro, c.endereco_cidade, c.endereco_estado, c.endereco_cep) AS endereco_completo,
    cc.periodo_id,
    cc.rota_id,
    cc.cobrador_id AS carteira_cobrador_id
   FROM (((public.prest_parcelas p
     JOIN public.prest_vendas v ON ((v.id = p.venda_id)))
     JOIN public.prest_clientes c ON ((c.id = v.cliente_id)))
     LEFT JOIN public.prest_carteira_cobranca cc ON (((cc.cliente_id = c.id) AND (cc.ativo = true))))
  WHERE (cc.id IS NOT NULL);


ALTER VIEW public.vw_parcelas_cobrador OWNER TO postgres;

--
-- Name: vw_parcelas_vencidas_cobrador; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_parcelas_vencidas_cobrador AS
 SELECT cc.cobrador_id,
    col.nome_completo AS cobrador_nome,
    c.id AS cliente_id,
    c.nome_completo AS cliente_nome,
    c.telefone AS cliente_telefone,
    p.id AS parcela_id,
    p.numero_parcela,
    p.valor_parcela,
    p.data_vencimento,
    (CURRENT_DATE - p.data_vencimento) AS dias_atraso,
    concat_ws(', '::text, concat_ws(' '::text, c.endereco_logradouro, c.endereco_numero), c.endereco_bairro, c.endereco_cidade) AS endereco_resumido
   FROM ((((public.prest_parcelas p
     JOIN public.prest_vendas v ON ((v.id = p.venda_id)))
     JOIN public.prest_clientes c ON ((c.id = v.cliente_id)))
     JOIN public.prest_carteira_cobranca cc ON (((cc.cliente_id = c.id) AND (cc.ativo = true))))
     JOIN public.prest_colaboradores col ON ((col.id = cc.cobrador_id)))
  WHERE (((p.status_parcela_codigo)::text = ANY (ARRAY[('PENDENTE'::character varying)::text, ('ATRASADA'::character varying)::text])) AND (p.data_vencimento < CURRENT_DATE))
  ORDER BY p.data_vencimento;


ALTER VIEW public.vw_parcelas_vencidas_cobrador OWNER TO postgres;

--
-- Name: vw_rota_dia_cobrador; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_rota_dia_cobrador AS
 SELECT r.id AS rota_id,
    r.cobrador_id,
    col.nome_completo AS cobrador_nome,
    r.nome_rota,
    r.dia_semana,
    r.ordem_execucao,
    c.id AS cliente_id,
    c.nome_completo AS cliente_nome,
    c.telefone,
    concat_ws(', '::text, concat_ws(' '::text, c.endereco_logradouro, c.endereco_numero), c.endereco_bairro, c.endereco_cidade) AS endereco,
    c.ponto_referencia,
    count(p.id) AS parcelas_pendentes,
    sum(p.valor_parcela) AS valor_total_pendente,
    min(p.data_vencimento) AS vencimento_mais_proximo
   FROM (((((public.prest_rotas_cobranca r
     JOIN public.prest_colaboradores col ON ((col.id = r.cobrador_id)))
     JOIN public.prest_carteira_cobranca cc ON (((cc.rota_id = r.id) AND (cc.ativo = true))))
     JOIN public.prest_clientes c ON ((c.id = cc.cliente_id)))
     LEFT JOIN public.prest_vendas v ON ((v.cliente_id = c.id)))
     LEFT JOIN public.prest_parcelas p ON (((p.venda_id = v.id) AND ((p.status_parcela_codigo)::text = ANY (ARRAY[('PENDENTE'::character varying)::text, ('ATRASADA'::character varying)::text])))))
  GROUP BY r.id, r.cobrador_id, col.nome_completo, r.nome_rota, r.dia_semana, r.ordem_execucao, c.id, c.nome_completo, c.telefone, c.endereco_logradouro, c.endereco_numero, c.endereco_bairro, c.endereco_cidade, c.ponto_referencia
  ORDER BY r.ordem_execucao, c.nome_completo;


ALTER VIEW public.vw_rota_dia_cobrador OWNER TO postgres;

--
-- Name: vw_usuario_modulos_disponiveis; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.vw_usuario_modulos_disponiveis AS
 SELECT DISTINCT u.id AS usuario_id,
    u.nome AS usuario_nome,
    m.id AS modulo_id,
    m.codigo AS modulo_codigo,
    m.nome AS modulo_nome,
    m.descricao AS modulo_descricao,
    m.icone,
    m.cor,
    m.rota,
    m.ordem,
        CASE
            WHEN ((a.status)::text = 'trial'::text) THEN 'trial'::text
            WHEN ((a.status)::text = 'ativa'::text) THEN 'ativa'::text
            WHEN (um.ativo = true) THEN 'acesso_direto'::text
            ELSE 'sem_acesso'::text
        END AS tipo_acesso,
    a.data_fim AS data_expiracao
   FROM ((((public.prest_usuarios u
     LEFT JOIN public.sis_assinaturas a ON (((a.usuario_id = u.id) AND ((a.status)::text = ANY (ARRAY[('ativa'::character varying)::text, ('trial'::character varying)::text])) AND ((a.data_fim IS NULL) OR (a.data_fim >= CURRENT_DATE)))))
     LEFT JOIN public.sis_plano_modulos pm ON ((pm.plano_id = a.plano_id)))
     LEFT JOIN public.sis_usuario_modulos um ON (((um.usuario_id = u.id) AND (um.ativo = true) AND ((um.data_fim IS NULL) OR (um.data_fim >= CURRENT_DATE)))))
     JOIN public.sis_modulos m ON (((m.id = pm.modulo_id) OR (m.id = um.modulo_id))))
  WHERE (m.ativo = true)
  ORDER BY m.ordem, m.nome;


ALTER VIEW public.vw_usuario_modulos_disponiveis OWNER TO postgres;

--
-- Name: delivery_admin_contas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_admin_contas ALTER COLUMN id SET DEFAULT nextval('public.delivery_admin_contas_id_seq'::regclass);


--
-- Name: delivery_categorias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_categorias ALTER COLUMN id SET DEFAULT nextval('public.delivery_categorias_id_seq'::regclass);


--
-- Name: delivery_clientes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_clientes ALTER COLUMN id SET DEFAULT nextval('public.delivery_clientes_id_seq'::regclass);


--
-- Name: delivery_complementos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_complementos ALTER COLUMN id SET DEFAULT nextval('public.delivery_complementos_id_seq'::regclass);


--
-- Name: delivery_configuracoes_estabelecimento id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_configuracoes_estabelecimento ALTER COLUMN id SET DEFAULT nextval('public.delivery_configuracoes_estabelecimento_id_seq'::regclass);


--
-- Name: delivery_enderecos_cliente id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_enderecos_cliente ALTER COLUMN id SET DEFAULT nextval('public.delivery_enderecos_cliente_id_seq'::regclass);


--
-- Name: delivery_entregadores id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregadores ALTER COLUMN id SET DEFAULT nextval('public.delivery_entregadores_id_seq'::regclass);


--
-- Name: delivery_entregas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregas ALTER COLUMN id SET DEFAULT nextval('public.delivery_entregas_id_seq'::regclass);


--
-- Name: delivery_estabelecimentos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_estabelecimentos ALTER COLUMN id SET DEFAULT nextval('public.delivery_estabelecimentos_id_seq'::regclass);


--
-- Name: delivery_movimentacoes_financeiras id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_movimentacoes_financeiras ALTER COLUMN id SET DEFAULT nextval('public.delivery_movimentacoes_financeiras_id_seq'::regclass);


--
-- Name: delivery_pedido_complementos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_complementos ALTER COLUMN id SET DEFAULT nextval('public.delivery_pedido_complementos_id_seq'::regclass);


--
-- Name: delivery_pedido_itens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_itens ALTER COLUMN id SET DEFAULT nextval('public.delivery_pedido_itens_id_seq'::regclass);


--
-- Name: delivery_pedidos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos ALTER COLUMN id SET DEFAULT nextval('public.delivery_pedidos_id_seq'::regclass);


--
-- Name: delivery_produto_complementos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produto_complementos ALTER COLUMN id SET DEFAULT nextval('public.delivery_produto_complementos_id_seq'::regclass);


--
-- Name: delivery_produtos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produtos ALTER COLUMN id SET DEFAULT nextval('public.delivery_produtos_id_seq'::regclass);


--
-- Name: delivery_promocoes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_promocoes ALTER COLUMN id SET DEFAULT nextval('public.delivery_promocoes_id_seq'::regclass);


--
-- Name: delivery_status_financeiro id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_financeiro ALTER COLUMN id SET DEFAULT nextval('public.delivery_status_financeiro_id_seq'::regclass);


--
-- Name: delivery_status_pedido id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_pedido ALTER COLUMN id SET DEFAULT nextval('public.delivery_status_pedido_id_seq'::regclass);


--
-- Name: delivery_tipos_entrega id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_entrega ALTER COLUMN id SET DEFAULT nextval('public.delivery_tipos_entrega_id_seq'::regclass);


--
-- Name: delivery_tipos_pagamento id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pagamento ALTER COLUMN id SET DEFAULT nextval('public.delivery_tipos_pagamento_id_seq'::regclass);


--
-- Name: delivery_tipos_pessoa id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pessoa ALTER COLUMN id SET DEFAULT nextval('public.delivery_tipos_pessoa_id_seq'::regclass);


--
-- Name: delivery_uso_promocoes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes ALTER COLUMN id SET DEFAULT nextval('public.delivery_uso_promocoes_id_seq'::regclass);


--
-- Name: delivery_usuarios_estabelecimento id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_usuarios_estabelecimento ALTER COLUMN id SET DEFAULT nextval('public.delivery_usuarios_estabelecimento_id_seq'::regclass);


--
-- Name: delivery_variacoes_produto id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_variacoes_produto ALTER COLUMN id SET DEFAULT nextval('public.delivery_variacoes_produto_id_seq'::regclass);


--
-- Name: delivery_admin_contas delivery_admin_contas_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_admin_contas
    ADD CONSTRAINT delivery_admin_contas_email_key UNIQUE (email);


--
-- Name: delivery_admin_contas delivery_admin_contas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_admin_contas
    ADD CONSTRAINT delivery_admin_contas_pkey PRIMARY KEY (id);


--
-- Name: delivery_admin_contas delivery_admin_contas_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_admin_contas
    ADD CONSTRAINT delivery_admin_contas_uuid_key UNIQUE (uuid);


--
-- Name: delivery_categorias delivery_categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_categorias
    ADD CONSTRAINT delivery_categorias_pkey PRIMARY KEY (id);


--
-- Name: delivery_categorias delivery_categorias_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_categorias
    ADD CONSTRAINT delivery_categorias_uuid_key UNIQUE (uuid);


--
-- Name: delivery_clientes delivery_clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_clientes
    ADD CONSTRAINT delivery_clientes_pkey PRIMARY KEY (id);


--
-- Name: delivery_clientes delivery_clientes_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_clientes
    ADD CONSTRAINT delivery_clientes_uuid_key UNIQUE (uuid);


--
-- Name: delivery_complementos delivery_complementos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_complementos
    ADD CONSTRAINT delivery_complementos_pkey PRIMARY KEY (id);


--
-- Name: delivery_complementos delivery_complementos_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_complementos
    ADD CONSTRAINT delivery_complementos_uuid_key UNIQUE (uuid);


--
-- Name: delivery_configuracoes_estabelecimento delivery_configuracoes_estabelecimento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_configuracoes_estabelecimento
    ADD CONSTRAINT delivery_configuracoes_estabelecimento_pkey PRIMARY KEY (id);


--
-- Name: delivery_enderecos_cliente delivery_enderecos_cliente_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_enderecos_cliente
    ADD CONSTRAINT delivery_enderecos_cliente_pkey PRIMARY KEY (id);


--
-- Name: delivery_enderecos_cliente delivery_enderecos_cliente_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_enderecos_cliente
    ADD CONSTRAINT delivery_enderecos_cliente_uuid_key UNIQUE (uuid);


--
-- Name: delivery_entregadores delivery_entregadores_cpf_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregadores
    ADD CONSTRAINT delivery_entregadores_cpf_key UNIQUE (cpf);


--
-- Name: delivery_entregadores delivery_entregadores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregadores
    ADD CONSTRAINT delivery_entregadores_pkey PRIMARY KEY (id);


--
-- Name: delivery_entregadores delivery_entregadores_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregadores
    ADD CONSTRAINT delivery_entregadores_uuid_key UNIQUE (uuid);


--
-- Name: delivery_entregas delivery_entregas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregas
    ADD CONSTRAINT delivery_entregas_pkey PRIMARY KEY (id);


--
-- Name: delivery_estabelecimentos delivery_estabelecimentos_cnpj_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_estabelecimentos
    ADD CONSTRAINT delivery_estabelecimentos_cnpj_key UNIQUE (cnpj);


--
-- Name: delivery_estabelecimentos delivery_estabelecimentos_email_principal_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_estabelecimentos
    ADD CONSTRAINT delivery_estabelecimentos_email_principal_key UNIQUE (email_principal);


--
-- Name: delivery_estabelecimentos delivery_estabelecimentos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_estabelecimentos
    ADD CONSTRAINT delivery_estabelecimentos_pkey PRIMARY KEY (id);


--
-- Name: delivery_estabelecimentos delivery_estabelecimentos_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_estabelecimentos
    ADD CONSTRAINT delivery_estabelecimentos_uuid_key UNIQUE (uuid);


--
-- Name: delivery_movimentacoes_financeiras delivery_movimentacoes_financeiras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_movimentacoes_financeiras
    ADD CONSTRAINT delivery_movimentacoes_financeiras_pkey PRIMARY KEY (id);


--
-- Name: delivery_pedido_complementos delivery_pedido_complementos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_complementos
    ADD CONSTRAINT delivery_pedido_complementos_pkey PRIMARY KEY (id);


--
-- Name: delivery_pedido_itens delivery_pedido_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_itens
    ADD CONSTRAINT delivery_pedido_itens_pkey PRIMARY KEY (id);


--
-- Name: delivery_pedidos delivery_pedidos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_pkey PRIMARY KEY (id);


--
-- Name: delivery_pedidos delivery_pedidos_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_uuid_key UNIQUE (uuid);


--
-- Name: delivery_produto_complementos delivery_produto_complementos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produto_complementos
    ADD CONSTRAINT delivery_produto_complementos_pkey PRIMARY KEY (id);


--
-- Name: delivery_produtos delivery_produtos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produtos
    ADD CONSTRAINT delivery_produtos_pkey PRIMARY KEY (id);


--
-- Name: delivery_produtos delivery_produtos_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produtos
    ADD CONSTRAINT delivery_produtos_uuid_key UNIQUE (uuid);


--
-- Name: delivery_promocoes delivery_promocoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_promocoes
    ADD CONSTRAINT delivery_promocoes_pkey PRIMARY KEY (id);


--
-- Name: delivery_promocoes delivery_promocoes_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_promocoes
    ADD CONSTRAINT delivery_promocoes_uuid_key UNIQUE (uuid);


--
-- Name: delivery_status_financeiro delivery_status_financeiro_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_financeiro
    ADD CONSTRAINT delivery_status_financeiro_codigo_key UNIQUE (codigo);


--
-- Name: delivery_status_financeiro delivery_status_financeiro_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_financeiro
    ADD CONSTRAINT delivery_status_financeiro_descricao_key UNIQUE (descricao);


--
-- Name: delivery_status_financeiro delivery_status_financeiro_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_financeiro
    ADD CONSTRAINT delivery_status_financeiro_pkey PRIMARY KEY (id);


--
-- Name: delivery_status_pedido delivery_status_pedido_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_pedido
    ADD CONSTRAINT delivery_status_pedido_codigo_key UNIQUE (codigo);


--
-- Name: delivery_status_pedido delivery_status_pedido_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_pedido
    ADD CONSTRAINT delivery_status_pedido_descricao_key UNIQUE (descricao);


--
-- Name: delivery_status_pedido delivery_status_pedido_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_status_pedido
    ADD CONSTRAINT delivery_status_pedido_pkey PRIMARY KEY (id);


--
-- Name: delivery_tipos_entrega delivery_tipos_entrega_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_entrega
    ADD CONSTRAINT delivery_tipos_entrega_codigo_key UNIQUE (codigo);


--
-- Name: delivery_tipos_entrega delivery_tipos_entrega_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_entrega
    ADD CONSTRAINT delivery_tipos_entrega_descricao_key UNIQUE (descricao);


--
-- Name: delivery_tipos_entrega delivery_tipos_entrega_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_entrega
    ADD CONSTRAINT delivery_tipos_entrega_pkey PRIMARY KEY (id);


--
-- Name: delivery_tipos_pagamento delivery_tipos_pagamento_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pagamento
    ADD CONSTRAINT delivery_tipos_pagamento_codigo_key UNIQUE (codigo);


--
-- Name: delivery_tipos_pagamento delivery_tipos_pagamento_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pagamento
    ADD CONSTRAINT delivery_tipos_pagamento_descricao_key UNIQUE (descricao);


--
-- Name: delivery_tipos_pagamento delivery_tipos_pagamento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pagamento
    ADD CONSTRAINT delivery_tipos_pagamento_pkey PRIMARY KEY (id);


--
-- Name: delivery_tipos_pessoa delivery_tipos_pessoa_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pessoa
    ADD CONSTRAINT delivery_tipos_pessoa_codigo_key UNIQUE (codigo);


--
-- Name: delivery_tipos_pessoa delivery_tipos_pessoa_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pessoa
    ADD CONSTRAINT delivery_tipos_pessoa_descricao_key UNIQUE (descricao);


--
-- Name: delivery_tipos_pessoa delivery_tipos_pessoa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_tipos_pessoa
    ADD CONSTRAINT delivery_tipos_pessoa_pkey PRIMARY KEY (id);


--
-- Name: delivery_uso_promocoes delivery_uso_promocoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes
    ADD CONSTRAINT delivery_uso_promocoes_pkey PRIMARY KEY (id);


--
-- Name: delivery_usuarios_estabelecimento delivery_usuarios_estabelecimento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_usuarios_estabelecimento
    ADD CONSTRAINT delivery_usuarios_estabelecimento_pkey PRIMARY KEY (id);


--
-- Name: delivery_usuarios_estabelecimento delivery_usuarios_estabelecimento_uuid_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_usuarios_estabelecimento
    ADD CONSTRAINT delivery_usuarios_estabelecimento_uuid_key UNIQUE (uuid);


--
-- Name: delivery_variacoes_produto delivery_variacoes_produto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_variacoes_produto
    ADD CONSTRAINT delivery_variacoes_produto_pkey PRIMARY KEY (id);


--
-- Name: prest_cobranca_configuracao prest_cobranca_configuracao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_configuracao
    ADD CONSTRAINT prest_cobranca_configuracao_pkey PRIMARY KEY (id);


--
-- Name: prest_cobranca_historico prest_cobranca_historico_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_historico
    ADD CONSTRAINT prest_cobranca_historico_pkey PRIMARY KEY (id);


--
-- Name: prest_cobranca_template prest_cobranca_template_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_template
    ADD CONSTRAINT prest_cobranca_template_pkey PRIMARY KEY (id);


--
-- Name: prest_configuracoes prest_configuracoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_configuracoes
    ADD CONSTRAINT prest_configuracoes_pkey PRIMARY KEY (id);


--
-- Name: prest_configuracoes prest_configuracoes_usuario_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_configuracoes
    ADD CONSTRAINT prest_configuracoes_usuario_id_key UNIQUE (usuario_id);


--
-- Name: prest_contas_pagar prest_contas_pagar_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_contas_pagar
    ADD CONSTRAINT prest_contas_pagar_pkey PRIMARY KEY (id);


--
-- Name: prest_estoque_movimentacoes prest_estoque_movimentacoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_estoque_movimentacoes
    ADD CONSTRAINT prest_estoque_movimentacoes_pkey PRIMARY KEY (id);


--
-- Name: prest_formas_pagamento prest_formas_pagamento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_formas_pagamento
    ADD CONSTRAINT prest_formas_pagamento_pkey PRIMARY KEY (id);


--
-- Name: prest_historico_cobranca prest_historico_cobranca_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_historico_cobranca
    ADD CONSTRAINT prest_historico_cobranca_pkey PRIMARY KEY (id);


--
-- Name: prest_orcamento_itens prest_orcamento_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamento_itens
    ADD CONSTRAINT prest_orcamento_itens_pkey PRIMARY KEY (id);


--
-- Name: prest_orcamentos prest_orcamentos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamentos
    ADD CONSTRAINT prest_orcamentos_pkey PRIMARY KEY (id);


--
-- Name: prest_periodos_cobranca prest_periodos_cobranca_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_periodos_cobranca
    ADD CONSTRAINT prest_periodos_cobranca_pkey PRIMARY KEY (id);


--
-- Name: prest_periodos_cobranca prest_periodos_cobranca_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_periodos_cobranca
    ADD CONSTRAINT prest_periodos_cobranca_unique UNIQUE (usuario_id, mes_referencia, ano_referencia);


--
-- Name: prest_regioes prest_regioes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_regioes
    ADD CONSTRAINT prest_regioes_pkey PRIMARY KEY (id);


--
-- Name: prest_rotas_cobranca prest_rotas_cobranca_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_rotas_cobranca
    ADD CONSTRAINT prest_rotas_cobranca_pkey PRIMARY KEY (id);


--
-- Name: prest_taxas_entrega prest_taxas_entrega_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_taxas_entrega
    ADD CONSTRAINT prest_taxas_entrega_pkey PRIMARY KEY (id);


--
-- Name: prest_tipos_despesa prest_tipos_despesa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_tipos_despesa
    ADD CONSTRAINT prest_tipos_despesa_pkey PRIMARY KEY (id);


--
-- Name: prest_vendedores prest_vendedores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendedores
    ADD CONSTRAINT prest_vendedores_pkey PRIMARY KEY (id);


--
-- Name: delivery_categorias uq_categoria_nome_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_categorias
    ADD CONSTRAINT uq_categoria_nome_estabelecimento UNIQUE (estabelecimento_id, nome);


--
-- Name: delivery_clientes uq_cliente_telefone; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_clientes
    ADD CONSTRAINT uq_cliente_telefone UNIQUE (telefone);


--
-- Name: prest_cobranca_configuracao uq_cobranca_config_usuario; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_configuracao
    ADD CONSTRAINT uq_cobranca_config_usuario UNIQUE (usuario_id);


--
-- Name: prest_cobranca_template uq_cobranca_template_usuario_tipo; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_template
    ADD CONSTRAINT uq_cobranca_template_usuario_tipo UNIQUE (usuario_id, tipo);


--
-- Name: delivery_promocoes uq_codigo_cupom_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_promocoes
    ADD CONSTRAINT uq_codigo_cupom_estabelecimento UNIQUE (estabelecimento_id, codigo_cupom);


--
-- Name: delivery_configuracoes_estabelecimento uq_config_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_configuracoes_estabelecimento
    ADD CONSTRAINT uq_config_estabelecimento UNIQUE (estabelecimento_id);


--
-- Name: delivery_pedidos uq_numero_pedido_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT uq_numero_pedido_estabelecimento UNIQUE (estabelecimento_id, numero_pedido);


--
-- Name: delivery_produto_complementos uq_produto_complemento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produto_complementos
    ADD CONSTRAINT uq_produto_complemento UNIQUE (produto_id, complemento_id);


--
-- Name: delivery_uso_promocoes uq_promocao_pedido; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes
    ADD CONSTRAINT uq_promocao_pedido UNIQUE (promocao_id, pedido_id);


--
-- Name: delivery_usuarios_estabelecimento uq_usuario_email_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_usuarios_estabelecimento
    ADD CONSTRAINT uq_usuario_email_estabelecimento UNIQUE (estabelecimento_id, email);


--
-- Name: idx_clientes_telefone; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_telefone ON public.delivery_clientes USING btree (telefone);


--
-- Name: idx_cobranca_config_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_config_ativo ON public.prest_cobranca_configuracao USING btree (ativo);


--
-- Name: idx_cobranca_config_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_config_usuario ON public.prest_cobranca_configuracao USING btree (usuario_id);


--
-- Name: idx_cobranca_historico_data_criacao; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_historico_data_criacao ON public.prest_cobranca_historico USING btree (data_criacao);


--
-- Name: idx_cobranca_historico_data_envio; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_historico_data_envio ON public.prest_cobranca_historico USING btree (data_envio);


--
-- Name: idx_cobranca_historico_parcela; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_historico_parcela ON public.prest_cobranca_historico USING btree (parcela_id);


--
-- Name: idx_cobranca_historico_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_historico_status ON public.prest_cobranca_historico USING btree (status);


--
-- Name: idx_cobranca_historico_tipo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_historico_tipo ON public.prest_cobranca_historico USING btree (tipo);


--
-- Name: idx_cobranca_historico_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_historico_usuario ON public.prest_cobranca_historico USING btree (usuario_id);


--
-- Name: idx_cobranca_template_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_template_ativo ON public.prest_cobranca_template USING btree (ativo);


--
-- Name: idx_cobranca_template_tipo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_template_tipo ON public.prest_cobranca_template USING btree (tipo);


--
-- Name: idx_cobranca_template_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cobranca_template_usuario ON public.prest_cobranca_template USING btree (usuario_id);


--
-- Name: idx_enderecos_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_enderecos_cliente ON public.delivery_enderecos_cliente USING btree (cliente_id);


--
-- Name: idx_estabelecimentos_nome_fulltext; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_estabelecimentos_nome_fulltext ON public.delivery_estabelecimentos USING gin (to_tsvector('portuguese'::regconfig, (nome_fantasia)::text));


--
-- Name: idx_estoque_mov_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_estoque_mov_data ON public.prest_estoque_movimentacoes USING btree (data_movimentacao);


--
-- Name: idx_estoque_mov_produto_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_estoque_mov_produto_id ON public.prest_estoque_movimentacoes USING btree (produto_id);


--
-- Name: idx_formas_pagamento_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_formas_pagamento_usuario_id ON public.prest_formas_pagamento USING btree (usuario_id);


--
-- Name: idx_hist_cobranca_cobrador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_hist_cobranca_cobrador_id ON public.prest_historico_cobranca USING btree (cobrador_id);


--
-- Name: idx_hist_cobranca_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_hist_cobranca_data ON public.prest_historico_cobranca USING btree (data_acao);


--
-- Name: idx_hist_cobranca_parcela_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_hist_cobranca_parcela_id ON public.prest_historico_cobranca USING btree (parcela_id);


--
-- Name: idx_movimentacoes_estabelecimento_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_movimentacoes_estabelecimento_data ON public.delivery_movimentacoes_financeiras USING btree (estabelecimento_id, data_movimento);


--
-- Name: idx_orcamento_itens_orcamento_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamento_itens_orcamento_id ON public.prest_orcamento_itens USING btree (orcamento_id);


--
-- Name: idx_orcamentos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamentos_status ON public.prest_orcamentos USING btree (status);


--
-- Name: idx_orcamentos_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamentos_usuario_id ON public.prest_orcamentos USING btree (usuario_id);


--
-- Name: idx_pedidos_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pedidos_cliente ON public.delivery_pedidos USING btree (cliente_id);


--
-- Name: idx_pedidos_estabelecimento_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pedidos_estabelecimento_data ON public.delivery_pedidos USING btree (estabelecimento_id, data_pedido);


--
-- Name: idx_pedidos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pedidos_status ON public.delivery_pedidos USING btree (status_id);


--
-- Name: idx_periodos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_periodos_status ON public.prest_periodos_cobranca USING btree (status);


--
-- Name: idx_periodos_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_periodos_usuario_id ON public.prest_periodos_cobranca USING btree (usuario_id);


--
-- Name: idx_prest_contas_pagar_compra_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_contas_pagar_compra_id ON public.prest_contas_pagar USING btree (compra_id);


--
-- Name: idx_prest_contas_pagar_data_pagamento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_contas_pagar_data_pagamento ON public.prest_contas_pagar USING btree (data_pagamento);


--
-- Name: idx_prest_contas_pagar_data_vencimento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_contas_pagar_data_vencimento ON public.prest_contas_pagar USING btree (data_vencimento);


--
-- Name: idx_prest_contas_pagar_fornecedor_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_contas_pagar_fornecedor_id ON public.prest_contas_pagar USING btree (fornecedor_id);


--
-- Name: idx_prest_contas_pagar_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_contas_pagar_status ON public.prest_contas_pagar USING btree (status);


--
-- Name: idx_prest_contas_pagar_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_contas_pagar_usuario_id ON public.prest_contas_pagar USING btree (usuario_id);


--
-- Name: idx_prest_tipos_despesa_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_tipos_despesa_ativo ON public.prest_tipos_despesa USING btree (ativo);


--
-- Name: idx_prest_tipos_despesa_grupo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_tipos_despesa_grupo ON public.prest_tipos_despesa USING btree (grupo);


--
-- Name: idx_prest_tipos_despesa_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_tipos_despesa_usuario ON public.prest_tipos_despesa USING btree (usuario_id);


--
-- Name: idx_produtos_ativo_disponivel; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_ativo_disponivel ON public.delivery_produtos USING btree (ativo, disponivel) WHERE ((ativo = true) AND (disponivel = true));


--
-- Name: idx_produtos_descricao_fulltext; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_descricao_fulltext ON public.delivery_produtos USING gin (to_tsvector('portuguese'::regconfig, descricao));


--
-- Name: idx_produtos_estabelecimento_categoria; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_estabelecimento_categoria ON public.delivery_produtos USING btree (estabelecimento_id, categoria_id);


--
-- Name: idx_produtos_nome_fulltext; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_nome_fulltext ON public.delivery_produtos USING gin (to_tsvector('portuguese'::regconfig, (nome)::text));


--
-- Name: idx_regioes_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_regioes_usuario_id ON public.prest_regioes USING btree (usuario_id);


--
-- Name: idx_rotas_cobrador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_rotas_cobrador_id ON public.prest_rotas_cobranca USING btree (cobrador_id);


--
-- Name: idx_rotas_periodo_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_rotas_periodo_id ON public.prest_rotas_cobranca USING btree (periodo_id);


--
-- Name: idx_taxas_entrega_localidade; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_taxas_entrega_localidade ON public.prest_taxas_entrega USING btree (cidade, bairro, cep);


--
-- Name: idx_taxas_entrega_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_taxas_entrega_usuario_id ON public.prest_taxas_entrega USING btree (usuario_id);


--
-- Name: idx_vendedores_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendedores_ativo ON public.prest_vendedores USING btree (ativo);


--
-- Name: idx_vendedores_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendedores_usuario_id ON public.prest_vendedores USING btree (usuario_id);


--
-- Name: prest_configuracoes set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_configuracoes FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_configuracoes DISABLE TRIGGER set_timestamp;


--
-- Name: prest_orcamentos set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_orcamentos FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_orcamentos DISABLE TRIGGER set_timestamp;


--
-- Name: prest_vendedores set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_vendedores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_vendedores DISABLE TRIGGER set_timestamp;


--
-- Name: delivery_clientes trigger_delivery_clientes_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_delivery_clientes_updated_at BEFORE UPDATE ON public.delivery_clientes FOR EACH ROW EXECUTE FUNCTION public.delivery_update_updated_at_column();


--
-- Name: delivery_estabelecimentos trigger_delivery_estabelecimentos_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_delivery_estabelecimentos_updated_at BEFORE UPDATE ON public.delivery_estabelecimentos FOR EACH ROW EXECUTE FUNCTION public.delivery_update_updated_at_column();


--
-- Name: delivery_pedidos trigger_delivery_generate_numero_pedido; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_delivery_generate_numero_pedido BEFORE INSERT ON public.delivery_pedidos FOR EACH ROW EXECUTE FUNCTION public.delivery_generate_numero_pedido();


--
-- Name: delivery_pedidos trigger_delivery_pedidos_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_delivery_pedidos_updated_at BEFORE UPDATE ON public.delivery_pedidos FOR EACH ROW EXECUTE FUNCTION public.delivery_update_updated_at_column();


--
-- Name: delivery_produtos trigger_delivery_produtos_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_delivery_produtos_updated_at BEFORE UPDATE ON public.delivery_produtos FOR EACH ROW EXECUTE FUNCTION public.delivery_update_updated_at_column();


--
-- Name: delivery_usuarios_estabelecimento trigger_delivery_usuarios_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_delivery_usuarios_updated_at BEFORE UPDATE ON public.delivery_usuarios_estabelecimento FOR EACH ROW EXECUTE FUNCTION public.delivery_update_updated_at_column();


--
-- Name: delivery_categorias delivery_categorias_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_categorias
    ADD CONSTRAINT delivery_categorias_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_clientes delivery_clientes_tipo_pessoa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_clientes
    ADD CONSTRAINT delivery_clientes_tipo_pessoa_id_fkey FOREIGN KEY (tipo_pessoa_id) REFERENCES public.delivery_tipos_pessoa(id);


--
-- Name: delivery_complementos delivery_complementos_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_complementos
    ADD CONSTRAINT delivery_complementos_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_configuracoes_estabelecimento delivery_configuracoes_estabelecimento_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_configuracoes_estabelecimento
    ADD CONSTRAINT delivery_configuracoes_estabelecimento_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_enderecos_cliente delivery_enderecos_cliente_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_enderecos_cliente
    ADD CONSTRAINT delivery_enderecos_cliente_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.delivery_clientes(id) ON DELETE CASCADE;


--
-- Name: delivery_entregadores delivery_entregadores_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregadores
    ADD CONSTRAINT delivery_entregadores_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_entregas delivery_entregas_entregador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregas
    ADD CONSTRAINT delivery_entregas_entregador_id_fkey FOREIGN KEY (entregador_id) REFERENCES public.delivery_entregadores(id) ON DELETE RESTRICT;


--
-- Name: delivery_entregas delivery_entregas_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_entregas
    ADD CONSTRAINT delivery_entregas_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.delivery_pedidos(id) ON DELETE RESTRICT;


--
-- Name: delivery_movimentacoes_financeiras delivery_movimentacoes_financeiras_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_movimentacoes_financeiras
    ADD CONSTRAINT delivery_movimentacoes_financeiras_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_movimentacoes_financeiras delivery_movimentacoes_financeiras_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_movimentacoes_financeiras
    ADD CONSTRAINT delivery_movimentacoes_financeiras_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.delivery_pedidos(id) ON DELETE SET NULL;


--
-- Name: delivery_movimentacoes_financeiras delivery_movimentacoes_financeiras_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_movimentacoes_financeiras
    ADD CONSTRAINT delivery_movimentacoes_financeiras_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.delivery_status_financeiro(id);


--
-- Name: delivery_pedido_complementos delivery_pedido_complementos_complemento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_complementos
    ADD CONSTRAINT delivery_pedido_complementos_complemento_id_fkey FOREIGN KEY (complemento_id) REFERENCES public.delivery_complementos(id) ON DELETE RESTRICT;


--
-- Name: delivery_pedido_complementos delivery_pedido_complementos_pedido_item_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_complementos
    ADD CONSTRAINT delivery_pedido_complementos_pedido_item_id_fkey FOREIGN KEY (pedido_item_id) REFERENCES public.delivery_pedido_itens(id) ON DELETE CASCADE;


--
-- Name: delivery_pedido_itens delivery_pedido_itens_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_itens
    ADD CONSTRAINT delivery_pedido_itens_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.delivery_pedidos(id) ON DELETE CASCADE;


--
-- Name: delivery_pedido_itens delivery_pedido_itens_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_itens
    ADD CONSTRAINT delivery_pedido_itens_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.delivery_produtos(id) ON DELETE RESTRICT;


--
-- Name: delivery_pedido_itens delivery_pedido_itens_variacao_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedido_itens
    ADD CONSTRAINT delivery_pedido_itens_variacao_id_fkey FOREIGN KEY (variacao_id) REFERENCES public.delivery_variacoes_produto(id) ON DELETE RESTRICT;


--
-- Name: delivery_pedidos delivery_pedidos_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.delivery_clientes(id) ON DELETE RESTRICT;


--
-- Name: delivery_pedidos delivery_pedidos_endereco_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_endereco_cliente_id_fkey FOREIGN KEY (endereco_cliente_id) REFERENCES public.delivery_enderecos_cliente(id) ON DELETE RESTRICT;


--
-- Name: delivery_pedidos delivery_pedidos_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_pedidos delivery_pedidos_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.delivery_status_pedido(id);


--
-- Name: delivery_pedidos delivery_pedidos_tipo_entrega_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_tipo_entrega_id_fkey FOREIGN KEY (tipo_entrega_id) REFERENCES public.delivery_tipos_entrega(id);


--
-- Name: delivery_pedidos delivery_pedidos_tipo_pagamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_pedidos
    ADD CONSTRAINT delivery_pedidos_tipo_pagamento_id_fkey FOREIGN KEY (tipo_pagamento_id) REFERENCES public.delivery_tipos_pagamento(id);


--
-- Name: delivery_produto_complementos delivery_produto_complementos_complemento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produto_complementos
    ADD CONSTRAINT delivery_produto_complementos_complemento_id_fkey FOREIGN KEY (complemento_id) REFERENCES public.delivery_complementos(id) ON DELETE CASCADE;


--
-- Name: delivery_produto_complementos delivery_produto_complementos_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produto_complementos
    ADD CONSTRAINT delivery_produto_complementos_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.delivery_produtos(id) ON DELETE CASCADE;


--
-- Name: delivery_produtos delivery_produtos_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produtos
    ADD CONSTRAINT delivery_produtos_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.delivery_categorias(id) ON DELETE RESTRICT;


--
-- Name: delivery_produtos delivery_produtos_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_produtos
    ADD CONSTRAINT delivery_produtos_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_promocoes delivery_promocoes_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_promocoes
    ADD CONSTRAINT delivery_promocoes_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_uso_promocoes delivery_uso_promocoes_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes
    ADD CONSTRAINT delivery_uso_promocoes_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.delivery_clientes(id) ON DELETE RESTRICT;


--
-- Name: delivery_uso_promocoes delivery_uso_promocoes_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes
    ADD CONSTRAINT delivery_uso_promocoes_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.delivery_pedidos(id) ON DELETE CASCADE;


--
-- Name: delivery_uso_promocoes delivery_uso_promocoes_promocao_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes
    ADD CONSTRAINT delivery_uso_promocoes_promocao_id_fkey FOREIGN KEY (promocao_id) REFERENCES public.delivery_promocoes(id) ON DELETE CASCADE;


--
-- Name: delivery_usuarios_estabelecimento delivery_usuarios_estabelecimento_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_usuarios_estabelecimento
    ADD CONSTRAINT delivery_usuarios_estabelecimento_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_variacoes_produto delivery_variacoes_produto_estabelecimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_variacoes_produto
    ADD CONSTRAINT delivery_variacoes_produto_estabelecimento_id_fkey FOREIGN KEY (estabelecimento_id) REFERENCES public.delivery_estabelecimentos(id) ON DELETE CASCADE;


--
-- Name: delivery_variacoes_produto delivery_variacoes_produto_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_variacoes_produto
    ADD CONSTRAINT delivery_variacoes_produto_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.delivery_produtos(id) ON DELETE CASCADE;


--
-- Name: prest_cobranca_configuracao prest_cobranca_configuracao_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_configuracao
    ADD CONSTRAINT prest_cobranca_configuracao_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_cobranca_historico prest_cobranca_historico_parcela_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_historico
    ADD CONSTRAINT prest_cobranca_historico_parcela_id_fkey FOREIGN KEY (parcela_id) REFERENCES public.prest_parcelas(id) ON DELETE CASCADE;


--
-- Name: prest_cobranca_historico prest_cobranca_historico_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_historico
    ADD CONSTRAINT prest_cobranca_historico_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_cobranca_template prest_cobranca_template_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cobranca_template
    ADD CONSTRAINT prest_cobranca_template_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_configuracoes prest_configuracoes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_configuracoes
    ADD CONSTRAINT prest_configuracoes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_contas_pagar prest_contas_pagar_compra_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_contas_pagar
    ADD CONSTRAINT prest_contas_pagar_compra_id_fkey FOREIGN KEY (compra_id) REFERENCES public.prest_compras(id) ON DELETE SET NULL;


--
-- Name: prest_contas_pagar prest_contas_pagar_forma_pagamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_contas_pagar
    ADD CONSTRAINT prest_contas_pagar_forma_pagamento_id_fkey FOREIGN KEY (forma_pagamento_id) REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL;


--
-- Name: prest_contas_pagar prest_contas_pagar_fornecedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_contas_pagar
    ADD CONSTRAINT prest_contas_pagar_fornecedor_id_fkey FOREIGN KEY (fornecedor_id) REFERENCES public.prest_fornecedores(id) ON DELETE SET NULL;


--
-- Name: prest_contas_pagar prest_contas_pagar_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_contas_pagar
    ADD CONSTRAINT prest_contas_pagar_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_estoque_movimentacoes prest_estoque_movimentacoes_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_estoque_movimentacoes
    ADD CONSTRAINT prest_estoque_movimentacoes_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE RESTRICT;


--
-- Name: prest_estoque_movimentacoes prest_estoque_movimentacoes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_estoque_movimentacoes
    ADD CONSTRAINT prest_estoque_movimentacoes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_estoque_movimentacoes prest_estoque_movimentacoes_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_estoque_movimentacoes
    ADD CONSTRAINT prest_estoque_movimentacoes_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON DELETE SET NULL;


--
-- Name: prest_formas_pagamento prest_formas_pagamento_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_formas_pagamento
    ADD CONSTRAINT prest_formas_pagamento_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_historico_cobranca prest_historico_cobranca_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_historico_cobranca
    ADD CONSTRAINT prest_historico_cobranca_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.prest_clientes(id) ON DELETE RESTRICT;


--
-- Name: prest_historico_cobranca prest_historico_cobranca_cobrador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_historico_cobranca
    ADD CONSTRAINT prest_historico_cobranca_cobrador_id_fkey FOREIGN KEY (cobrador_id) REFERENCES public.prest_colaboradores(id) ON DELETE RESTRICT;


--
-- Name: prest_historico_cobranca prest_historico_cobranca_parcela_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_historico_cobranca
    ADD CONSTRAINT prest_historico_cobranca_parcela_id_fkey FOREIGN KEY (parcela_id) REFERENCES public.prest_parcelas(id) ON DELETE CASCADE;


--
-- Name: prest_historico_cobranca prest_historico_cobranca_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_historico_cobranca
    ADD CONSTRAINT prest_historico_cobranca_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_orcamento_itens prest_orcamento_itens_orcamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamento_itens
    ADD CONSTRAINT prest_orcamento_itens_orcamento_id_fkey FOREIGN KEY (orcamento_id) REFERENCES public.prest_orcamentos(id) ON DELETE CASCADE;


--
-- Name: prest_orcamento_itens prest_orcamento_itens_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamento_itens
    ADD CONSTRAINT prest_orcamento_itens_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE RESTRICT;


--
-- Name: prest_orcamentos prest_orcamentos_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamentos
    ADD CONSTRAINT prest_orcamentos_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.prest_clientes(id) ON DELETE SET NULL;


--
-- Name: prest_orcamentos prest_orcamentos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamentos
    ADD CONSTRAINT prest_orcamentos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_orcamentos prest_orcamentos_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_orcamentos
    ADD CONSTRAINT prest_orcamentos_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON DELETE SET NULL;


--
-- Name: prest_periodos_cobranca prest_periodos_cobranca_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_periodos_cobranca
    ADD CONSTRAINT prest_periodos_cobranca_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_regioes prest_regioes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_regioes
    ADD CONSTRAINT prest_regioes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_rotas_cobranca prest_rotas_cobranca_cobrador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_rotas_cobranca
    ADD CONSTRAINT prest_rotas_cobranca_cobrador_id_fkey FOREIGN KEY (cobrador_id) REFERENCES public.prest_colaboradores(id) ON DELETE RESTRICT;


--
-- Name: prest_rotas_cobranca prest_rotas_cobranca_periodo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_rotas_cobranca
    ADD CONSTRAINT prest_rotas_cobranca_periodo_id_fkey FOREIGN KEY (periodo_id) REFERENCES public.prest_periodos_cobranca(id) ON DELETE CASCADE;


--
-- Name: prest_rotas_cobranca prest_rotas_cobranca_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_rotas_cobranca
    ADD CONSTRAINT prest_rotas_cobranca_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_taxas_entrega prest_taxas_entrega_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_taxas_entrega
    ADD CONSTRAINT prest_taxas_entrega_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_tipos_despesa prest_tipos_despesa_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_tipos_despesa
    ADD CONSTRAINT prest_tipos_despesa_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_vendedores prest_vendedores_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendedores
    ADD CONSTRAINT prest_vendedores_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict urkWm5N3NbtKz1yUh8PySpEdMpvo2hviGOSgk2Jqgx4WDkRe6SVuxKadDKJeDNv

