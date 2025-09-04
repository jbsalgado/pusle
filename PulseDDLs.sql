--
-- PostgreSQL database dump
--

-- Dumped from database version 17.5
-- Dumped by pg_dump version 17.5

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

--
-- Name: uuid-ossp; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS "uuid-ossp" WITH SCHEMA public;


--
-- Name: EXTENSION "uuid-ossp"; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION "uuid-ossp" IS 'generate universally unique identifiers (UUIDs)';


--
-- Name: tipo_indicador; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipo_indicador AS ENUM (
    'SAUDE_BASICO',
    'DEMOGRAFICO',
    'SOCIOECONOMICO',
    'MORBIDADE',
    'MORTALIDADE',
    'RECURSOS_SAUDE',
    'COBERTURA_SERVICOS',
    'QUALIDADE_APS',
    'DESEMPENHO_GERAL',
    'FINANCEIRO',
    'SATISFACAO_USUARIO',
    'OUTRO',
    'OPERACIONAL',
    'CLIENTE',
    'QUALIDADE',
    'SUSTENTABILIDADE'
);


ALTER TYPE public.tipo_indicador OWNER TO postgres;

--
-- Name: tipo_meta; Type: TYPE; Schema: public; Owner: postgres
--

CREATE TYPE public.tipo_meta AS ENUM (
    'MINIMO_ACEITAVEL',
    'MAXIMO_ACEITAVEL',
    'VALOR_EXATO_ESPERADO',
    'FAIXA_IDEAL',
    'PERCENTUAL_MELHORIA'
);


ALTER TYPE public.tipo_meta OWNER TO postgres;

--
-- Name: delivery_generate_numero_pedido(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delivery_generate_numero_pedido() RETURNS trigger
    LANGUAGE plpgsql
    AS $_$
BEGIN
    IF NEW.numero_pedido IS NULL THEN
        NEW.numero_pedido := LPAD((
            SELECT COALESCE(MAX(CAST(numero_pedido AS INTEGER)), 0) + 1
            FROM delivery_pedidos
            WHERE estabelecimento_id = NEW.estabelecimento_id
            AND numero_pedido ~ '^\d+$'
        )::TEXT, 6, '0');
    END IF;
    RETURN NEW;
END;
$_$;


ALTER FUNCTION public.delivery_generate_numero_pedido() OWNER TO postgres;

--
-- Name: delivery_update_updated_at_column(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delivery_update_updated_at_column() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.delivery_update_updated_at_column() OWNER TO postgres;

--
-- Name: trigger_set_timestamp(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.trigger_set_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.data_atualizacao = NOW();
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.trigger_set_timestamp() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: social_account; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.social_account (
    id integer NOT NULL,
    user_id integer,
    provider character varying(255) NOT NULL,
    client_id character varying(255) NOT NULL,
    data text,
    code character varying(32) DEFAULT NULL::character varying,
    created_at integer,
    email character varying(255) DEFAULT NULL::character varying,
    username character varying(255) DEFAULT NULL::character varying
);


ALTER TABLE public.social_account OWNER TO postgres;

--
-- Name: account_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.account_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.account_id_seq OWNER TO postgres;

--
-- Name: account_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.account_id_seq OWNED BY public.social_account.id;


--
-- Name: auth_assignment; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.auth_assignment (
    item_name character varying(64) NOT NULL,
    user_id character varying(64) NOT NULL,
    created_at integer
);


ALTER TABLE public.auth_assignment OWNER TO postgres;

--
-- Name: auth_item; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.auth_item (
    name character varying(64) NOT NULL,
    type smallint NOT NULL,
    description text,
    rule_name character varying(64),
    data bytea,
    created_at integer,
    updated_at integer
);


ALTER TABLE public.auth_item OWNER TO postgres;

--
-- Name: auth_item_child; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.auth_item_child (
    parent character varying(64) NOT NULL,
    child character varying(64) NOT NULL
);


ALTER TABLE public.auth_item_child OWNER TO postgres;

--
-- Name: auth_rule; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.auth_rule (
    name character varying(64) NOT NULL,
    data bytea,
    created_at integer,
    updated_at integer
);


ALTER TABLE public.auth_rule OWNER TO postgres;

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
    estabelecimento_id integer NOT NULL,
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
          WHERE ((delivery_status_pedido.codigo)::text = ANY ((ARRAY['ENTREGUE'::character varying, 'CONCLUIDO'::character varying])::text[]))))
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
              WHERE ((delivery_status_pedido.codigo)::text = ANY ((ARRAY['ENTREGUE'::character varying, 'CONCLUIDO'::character varying])::text[])))) THEN 1
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
-- Name: ind_atributos_qualidade_desempenho; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_atributos_qualidade_desempenho (
    id_atributo_qd integer NOT NULL,
    id_indicador integer NOT NULL,
    padrao_ouro_referencia character varying(255),
    faixa_critica_inferior numeric,
    faixa_critica_superior numeric,
    faixa_alerta_inferior numeric,
    faixa_alerta_superior numeric,
    faixa_satisfatoria_inferior numeric,
    faixa_satisfatoria_superior numeric,
    metodo_pontuacao text,
    peso_indicador numeric,
    fator_impacto smallint,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ind_atributos_qualidade_desempenho_fator_impacto_check CHECK (((fator_impacto >= 1) AND (fator_impacto <= 5)))
);


ALTER TABLE public.ind_atributos_qualidade_desempenho OWNER TO postgres;

--
-- Name: TABLE ind_atributos_qualidade_desempenho; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_atributos_qualidade_desempenho IS 'Detalhes específicos para indicadores de qualidade ou desempenho, como faixas de avaliação e pesos.';


--
-- Name: ind_atributos_qualidade_desempenho_id_atributo_qd_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_atributos_qualidade_desempenho_id_atributo_qd_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_atributos_qualidade_desempenho_id_atributo_qd_seq OWNER TO postgres;

--
-- Name: ind_atributos_qualidade_desempenho_id_atributo_qd_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_atributos_qualidade_desempenho_id_atributo_qd_seq OWNED BY public.ind_atributos_qualidade_desempenho.id_atributo_qd;


--
-- Name: ind_categorias_desagregacao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_categorias_desagregacao (
    id_categoria_desagregacao integer NOT NULL,
    nome_categoria character varying(255) NOT NULL,
    descricao text,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_categorias_desagregacao OWNER TO postgres;

--
-- Name: TABLE ind_categorias_desagregacao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_categorias_desagregacao IS 'Define os eixos pelos quais os dados dos indicadores podem ser quebrados/analisados.';


--
-- Name: ind_categorias_desagregacao_id_categoria_desagregacao_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_categorias_desagregacao_id_categoria_desagregacao_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_categorias_desagregacao_id_categoria_desagregacao_seq OWNER TO postgres;

--
-- Name: ind_categorias_desagregacao_id_categoria_desagregacao_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_categorias_desagregacao_id_categoria_desagregacao_seq OWNED BY public.ind_categorias_desagregacao.id_categoria_desagregacao;


--
-- Name: ind_definicoes_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_definicoes_indicadores (
    id_indicador integer NOT NULL,
    cod_indicador character varying(50),
    nome_indicador character varying(512) NOT NULL,
    descricao_completa text NOT NULL,
    conceito text,
    justificativa text,
    metodo_calculo text,
    interpretacao text,
    limitacoes text,
    observacoes_gerais text,
    id_dimensao integer,
    id_unidade_medida integer NOT NULL,
    id_periodicidade_ideal_medicao integer,
    id_periodicidade_ideal_divulgacao integer,
    id_fonte_padrao integer,
    tipo_especifico public.tipo_indicador DEFAULT 'OUTRO'::public.tipo_indicador,
    polaridade character varying(50),
    data_inicio_validade date DEFAULT CURRENT_DATE,
    data_fim_validade date,
    responsavel_tecnico character varying(255),
    nota_tecnica_url character varying(512),
    palavras_chave text,
    versao smallint DEFAULT 1,
    ativo boolean DEFAULT true,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    descricao_numerador text,
    descricao_denominador text,
    CONSTRAINT ind_definicoes_indicadores_polaridade_check CHECK (((polaridade)::text = ANY ((ARRAY['QUANTO_MAIOR_MELHOR'::character varying, 'QUANTO_MENOR_MELHOR'::character varying, 'DENTRO_DA_FAIXA_MELHOR'::character varying, 'NEUTRO'::character varying])::text[])))
);


ALTER TABLE public.ind_definicoes_indicadores OWNER TO postgres;

--
-- Name: TABLE ind_definicoes_indicadores; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_definicoes_indicadores IS 'Catálogo central de todos os indicadores monitorados.';


--
-- Name: COLUMN ind_definicoes_indicadores.polaridade; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.ind_definicoes_indicadores.polaridade IS 'Indica a direção desejável do valor do indicador para melhor desempenho.';


--
-- Name: COLUMN ind_definicoes_indicadores.versao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.ind_definicoes_indicadores.versao IS 'Controla versões da ficha técnica do indicador caso haja mudanças metodológicas.';


--
-- Name: ind_definicoes_indicadores_id_indicador_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_definicoes_indicadores_id_indicador_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_definicoes_indicadores_id_indicador_seq OWNER TO postgres;

--
-- Name: ind_definicoes_indicadores_id_indicador_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_definicoes_indicadores_id_indicador_seq OWNED BY public.ind_definicoes_indicadores.id_indicador;


--
-- Name: ind_dimensoes_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_dimensoes_indicadores (
    id_dimensao integer NOT NULL,
    nome_dimensao character varying(255) NOT NULL,
    descricao text,
    id_dimensao_pai integer,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_dimensoes_indicadores OWNER TO postgres;

--
-- Name: COLUMN ind_dimensoes_indicadores.id_dimensao_pai; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.ind_dimensoes_indicadores.id_dimensao_pai IS 'Permite criar hierarquias, como subdimensões.';


--
-- Name: ind_dimensoes_indicadores_id_dimensao_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_dimensoes_indicadores_id_dimensao_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_dimensoes_indicadores_id_dimensao_seq OWNER TO postgres;

--
-- Name: ind_dimensoes_indicadores_id_dimensao_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_dimensoes_indicadores_id_dimensao_seq OWNED BY public.ind_dimensoes_indicadores.id_dimensao;


--
-- Name: ind_fontes_dados; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_fontes_dados (
    id_fonte integer NOT NULL,
    nome_fonte character varying(255) NOT NULL,
    descricao text,
    url_referencia character varying(512),
    confiabilidade_estimada smallint,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ind_fontes_dados_confiabilidade_estimada_check CHECK (((confiabilidade_estimada >= 1) AND (confiabilidade_estimada <= 5)))
);


ALTER TABLE public.ind_fontes_dados OWNER TO postgres;

--
-- Name: TABLE ind_fontes_dados; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_fontes_dados IS 'Registra a origem dos dados utilizados para calcular os indicadores.';


--
-- Name: COLUMN ind_fontes_dados.confiabilidade_estimada; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.ind_fontes_dados.confiabilidade_estimada IS 'Uma estimativa subjetiva da confiabilidade da fonte.';


--
-- Name: ind_fontes_dados_id_fonte_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_fontes_dados_id_fonte_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_fontes_dados_id_fonte_seq OWNER TO postgres;

--
-- Name: ind_fontes_dados_id_fonte_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_fontes_dados_id_fonte_seq OWNED BY public.ind_fontes_dados.id_fonte;


--
-- Name: ind_metas_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_metas_indicadores (
    id_meta integer NOT NULL,
    id_indicador integer NOT NULL,
    descricao_meta character varying(512),
    valor_meta_referencia_1 numeric NOT NULL,
    valor_meta_referencia_2 numeric,
    tipo_de_meta public.tipo_meta NOT NULL,
    data_inicio_vigencia date NOT NULL,
    data_fim_vigencia date,
    id_nivel_abrangencia_aplicavel integer,
    justificativa_meta text,
    fonte_meta character varying(255),
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_metas_indicadores OWNER TO postgres;

--
-- Name: TABLE ind_metas_indicadores; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_metas_indicadores IS 'Armazena as metas estabelecidas para cada indicador.';


--
-- Name: ind_metas_indicadores_id_meta_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_metas_indicadores_id_meta_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_metas_indicadores_id_meta_seq OWNER TO postgres;

--
-- Name: ind_metas_indicadores_id_meta_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_metas_indicadores_id_meta_seq OWNED BY public.ind_metas_indicadores.id_meta;


--
-- Name: ind_niveis_abrangencia; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_niveis_abrangencia (
    id_nivel_abrangencia integer NOT NULL,
    nome_nivel character varying(150) NOT NULL,
    descricao text,
    tipo_nivel character varying(50) DEFAULT 'GEOGRAFICO'::character varying,
    id_nivel_pai integer,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_niveis_abrangencia OWNER TO postgres;

--
-- Name: TABLE ind_niveis_abrangencia; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_niveis_abrangencia IS 'Escopo de aplicação ou análise do indicador (geográfico, organizacional, etc.).';


--
-- Name: ind_niveis_abrangencia_id_nivel_abrangencia_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_niveis_abrangencia_id_nivel_abrangencia_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_niveis_abrangencia_id_nivel_abrangencia_seq OWNER TO postgres;

--
-- Name: ind_niveis_abrangencia_id_nivel_abrangencia_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_niveis_abrangencia_id_nivel_abrangencia_seq OWNED BY public.ind_niveis_abrangencia.id_nivel_abrangencia;


--
-- Name: ind_opcoes_desagregacao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_opcoes_desagregacao (
    id_opcao_desagregacao integer NOT NULL,
    id_categoria_desagregacao integer NOT NULL,
    valor_opcao character varying(255) NOT NULL,
    codigo_opcao character varying(50),
    descricao_opcao text,
    ordem_apresentacao integer DEFAULT 0,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_opcoes_desagregacao OWNER TO postgres;

--
-- Name: TABLE ind_opcoes_desagregacao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_opcoes_desagregacao IS 'Valores específicos para cada categoria de desagregação.';


--
-- Name: ind_opcoes_desagregacao_id_opcao_desagregacao_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_opcoes_desagregacao_id_opcao_desagregacao_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_opcoes_desagregacao_id_opcao_desagregacao_seq OWNER TO postgres;

--
-- Name: ind_opcoes_desagregacao_id_opcao_desagregacao_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_opcoes_desagregacao_id_opcao_desagregacao_seq OWNED BY public.ind_opcoes_desagregacao.id_opcao_desagregacao;


--
-- Name: ind_periodicidades; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_periodicidades (
    id_periodicidade integer NOT NULL,
    nome_periodicidade character varying(100) NOT NULL,
    descricao text,
    intervalo_em_dias integer,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_periodicidades OWNER TO postgres;

--
-- Name: TABLE ind_periodicidades; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_periodicidades IS 'Frequência com que um indicador é medido ou atualizado.';


--
-- Name: ind_periodicidades_id_periodicidade_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_periodicidades_id_periodicidade_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_periodicidades_id_periodicidade_seq OWNER TO postgres;

--
-- Name: ind_periodicidades_id_periodicidade_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_periodicidades_id_periodicidade_seq OWNED BY public.ind_periodicidades.id_periodicidade;


--
-- Name: ind_relacoes_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_relacoes_indicadores (
    id_relacao integer NOT NULL,
    id_indicador_origem integer NOT NULL,
    id_indicador_destino integer NOT NULL,
    tipo_relacao character varying(100) NOT NULL,
    descricao_relacao text,
    peso_relacao numeric,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ind_relacoes_indicadores_check CHECK ((id_indicador_origem <> id_indicador_destino))
);


ALTER TABLE public.ind_relacoes_indicadores OWNER TO postgres;

--
-- Name: TABLE ind_relacoes_indicadores; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_relacoes_indicadores IS 'Define interdependências ou agrupamentos entre indicadores.';


--
-- Name: ind_relacoes_indicadores_id_relacao_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_relacoes_indicadores_id_relacao_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_relacoes_indicadores_id_relacao_seq OWNER TO postgres;

--
-- Name: ind_relacoes_indicadores_id_relacao_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_relacoes_indicadores_id_relacao_seq OWNED BY public.ind_relacoes_indicadores.id_relacao;


--
-- Name: ind_unidades_medida; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_unidades_medida (
    id_unidade integer NOT NULL,
    sigla_unidade character varying(50) NOT NULL,
    descricao_unidade character varying(255) NOT NULL,
    tipo_dado_associado character varying(50) DEFAULT 'NUMERICO'::character varying,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_unidades_medida OWNER TO postgres;

--
-- Name: TABLE ind_unidades_medida; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_unidades_medida IS 'Define como os valores dos indicadores são expressos.';


--
-- Name: ind_unidades_medida_id_unidade_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_unidades_medida_id_unidade_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_unidades_medida_id_unidade_seq OWNER TO postgres;

--
-- Name: ind_unidades_medida_id_unidade_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_unidades_medida_id_unidade_seq OWNED BY public.ind_unidades_medida.id_unidade;


--
-- Name: ind_valores_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_valores_indicadores (
    id_valor integer NOT NULL,
    id_indicador integer NOT NULL,
    data_referencia date NOT NULL,
    id_nivel_abrangencia integer NOT NULL,
    codigo_especifico_abrangencia character varying(100),
    localidade_especifica_nome character varying(255),
    valor numeric NOT NULL,
    numerador numeric,
    denominador numeric,
    id_fonte_dado_especifica integer,
    data_coleta_dado date,
    confianca_intervalo_inferior numeric,
    confianca_intervalo_superior numeric,
    analise_qualitativa_valor text,
    data_publicacao_valor timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.ind_valores_indicadores OWNER TO postgres;

--
-- Name: TABLE ind_valores_indicadores; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_valores_indicadores IS 'Armazena os valores medidos dos indicadores ao longo do tempo e para diferentes níveis.';


--
-- Name: COLUMN ind_valores_indicadores.data_referencia; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.ind_valores_indicadores.data_referencia IS 'Data de competência do valor do indicador.';


--
-- Name: ind_valores_indicadores_desagregacoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.ind_valores_indicadores_desagregacoes (
    id_valor_indicador integer NOT NULL,
    id_opcao_desagregacao integer NOT NULL
);


ALTER TABLE public.ind_valores_indicadores_desagregacoes OWNER TO postgres;

--
-- Name: TABLE ind_valores_indicadores_desagregacoes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.ind_valores_indicadores_desagregacoes IS 'Permite que um único valor de indicador seja analisado por múltiplas facetas de desagregação.';


--
-- Name: ind_valores_indicadores_id_valor_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.ind_valores_indicadores_id_valor_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ind_valores_indicadores_id_valor_seq OWNER TO postgres;

--
-- Name: ind_valores_indicadores_id_valor_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.ind_valores_indicadores_id_valor_seq OWNED BY public.ind_valores_indicadores.id_valor;


--
-- Name: indica_producao_diaria; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.indica_producao_diaria (
    id integer NOT NULL,
    terceiro_id integer NOT NULL,
    data date NOT NULL,
    pecas_produzidas integer NOT NULL,
    horas_trabalhadas numeric(5,2)
);


ALTER TABLE public.indica_producao_diaria OWNER TO postgres;

--
-- Name: TABLE indica_producao_diaria; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.indica_producao_diaria IS 'Armazena a produção consolidada por dia de cada terceiro, base para KPIs de produtividade.';


--
-- Name: indica_producao_diaria_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.indica_producao_diaria_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.indica_producao_diaria_id_seq OWNER TO postgres;

--
-- Name: indica_producao_diaria_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.indica_producao_diaria_id_seq OWNED BY public.indica_producao_diaria.id;


--
-- Name: indica_qualidade_defeitos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.indica_qualidade_defeitos (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    lote_id integer NOT NULL,
    data_registro date DEFAULT CURRENT_DATE NOT NULL,
    tipo_defeito character varying(100) NOT NULL,
    quantidade integer NOT NULL
);


ALTER TABLE public.indica_qualidade_defeitos OWNER TO postgres;

--
-- Name: indica_qualidade_defeitos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.indica_qualidade_defeitos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.indica_qualidade_defeitos_id_seq OWNER TO postgres;

--
-- Name: indica_qualidade_defeitos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.indica_qualidade_defeitos_id_seq OWNED BY public.indica_qualidade_defeitos.id;


--
-- Name: indica_tempos_producao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.indica_tempos_producao (
    id integer NOT NULL,
    lote_id integer NOT NULL,
    etapa_id integer NOT NULL,
    inicio timestamp without time zone,
    fim timestamp without time zone,
    tempo_total_minutos integer
);


ALTER TABLE public.indica_tempos_producao OWNER TO postgres;

--
-- Name: TABLE indica_tempos_producao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.indica_tempos_producao IS 'Registra os tempos de início e fim de cada lote em cada etapa, base para KPIs de tempo.';


--
-- Name: indica_tempos_producao_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.indica_tempos_producao_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.indica_tempos_producao_id_seq OWNER TO postgres;

--
-- Name: indica_tempos_producao_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.indica_tempos_producao_id_seq OWNED BY public.indica_tempos_producao.id;


--
-- Name: many_sys_modulos_has_many_ind_dimensoes_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.many_sys_modulos_has_many_ind_dimensoes_indicadores (
    id_sys_modulos integer NOT NULL,
    id_dimensao_ind_dimensoes_indicadores integer NOT NULL
);


ALTER TABLE public.many_sys_modulos_has_many_ind_dimensoes_indicadores OWNER TO postgres;

--
-- Name: many_sys_modulos_has_many_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.many_sys_modulos_has_many_user (
    id_sys_modulos integer NOT NULL,
    id_user integer NOT NULL
);


ALTER TABLE public.many_sys_modulos_has_many_user OWNER TO postgres;

--
-- Name: migration; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migration (
    version character varying(180) NOT NULL,
    apply_time integer
);


ALTER TABLE public.migration OWNER TO postgres;

--
-- Name: prest_clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_clientes (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome_completo character varying(150) NOT NULL,
    cpf character varying(11),
    telefone character varying(20),
    email character varying(100),
    endereco_logradouro character varying(255),
    endereco_numero character varying(20),
    endereco_complemento character varying(100),
    endereco_bairro character varying(100),
    endereco_cidade character varying(100),
    endereco_estado character varying(2),
    endereco_cep character varying(8),
    ponto_referencia text,
    observacoes text,
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_clientes OWNER TO postgres;

--
-- Name: TABLE prest_clientes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_clientes IS 'Cadastro de clientes, cada um associado a um prestanista.';


--
-- Name: COLUMN prest_clientes.usuario_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_clientes.usuario_id IS 'Chave estrangeira para a tabela de usuários, garantindo o isolamento dos dados.';


--
-- Name: COLUMN prest_clientes.ponto_referencia; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_clientes.ponto_referencia IS 'Informações adicionais para localizar o endereço do cliente.';


--
-- Name: COLUMN prest_clientes.ativo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_clientes.ativo IS 'Indica se o cliente está ativo no sistema (para exclusão lógica).';


--
-- Name: prest_parcelas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_parcelas (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    venda_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    numero_parcela integer NOT NULL,
    valor_parcela numeric(10,2) NOT NULL,
    data_vencimento date NOT NULL,
    status_parcela_codigo character varying(20) DEFAULT 'PENDENTE'::character varying NOT NULL,
    data_pagamento date,
    valor_pago numeric(10,2),
    observacoes text
);


ALTER TABLE public.prest_parcelas OWNER TO postgres;

--
-- Name: TABLE prest_parcelas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_parcelas IS 'Detalha cada parcela de uma venda a prazo.';


--
-- Name: COLUMN prest_parcelas.usuario_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_parcelas.usuario_id IS 'ID do usuário denormalizado para facilitar consultas de cobrança por vendedor.';


--
-- Name: COLUMN prest_parcelas.numero_parcela; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_parcelas.numero_parcela IS 'O número da parcela (ex: 1, 2, 3...).';


--
-- Name: COLUMN prest_parcelas.data_vencimento; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_parcelas.data_vencimento IS 'Data limite para o pagamento da parcela.';


--
-- Name: COLUMN prest_parcelas.data_pagamento; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_parcelas.data_pagamento IS 'Data em que o pagamento foi efetuado.';


--
-- Name: COLUMN prest_parcelas.valor_pago; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_parcelas.valor_pago IS 'Valor efetivamente pago, útil para registrar pagamentos parciais ou com juros.';


--
-- Name: prest_produtos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_produtos (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(150) NOT NULL,
    descricao text,
    codigo_referencia character varying(50),
    preco_custo numeric(10,2) DEFAULT 0.00 NOT NULL,
    preco_venda_sugerido numeric(10,2) NOT NULL,
    estoque_atual integer DEFAULT 0 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_produtos OWNER TO postgres;

--
-- Name: TABLE prest_produtos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_produtos IS 'Cadastro de produtos de cada prestanista.';


--
-- Name: COLUMN prest_produtos.preco_custo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.preco_custo IS 'Preço que o vendedor pagou pelo produto.';


--
-- Name: COLUMN prest_produtos.preco_venda_sugerido; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.preco_venda_sugerido IS 'Preço de venda padrão para o produto.';


--
-- Name: COLUMN prest_produtos.estoque_atual; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.estoque_atual IS 'Quantidade do produto em estoque.';


--
-- Name: prest_status_parcela; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_status_parcela (
    codigo character varying(20) NOT NULL,
    descricao character varying(100) NOT NULL
);


ALTER TABLE public.prest_status_parcela OWNER TO postgres;

--
-- Name: prest_status_venda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_status_venda (
    codigo character varying(20) NOT NULL,
    descricao character varying(100) NOT NULL
);


ALTER TABLE public.prest_status_venda OWNER TO postgres;

--
-- Name: prest_usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_usuarios (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    nome character varying(100) NOT NULL,
    email character varying(100) NOT NULL,
    hash_senha character varying(255) NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_usuarios OWNER TO postgres;

--
-- Name: TABLE prest_usuarios; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_usuarios IS 'Armazena os dados dos prestanistas (vendedores) que usam o sistema.';


--
-- Name: COLUMN prest_usuarios.id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.id IS 'Identificador único do usuário (UUID).';


--
-- Name: COLUMN prest_usuarios.nome; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.nome IS 'Nome completo do usuário.';


--
-- Name: COLUMN prest_usuarios.email; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.email IS 'Email para login e contato, deve ser único.';


--
-- Name: COLUMN prest_usuarios.hash_senha; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.hash_senha IS 'Senha do usuário armazenada de forma segura (hash).';


--
-- Name: prest_venda_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_venda_itens (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    venda_id uuid NOT NULL,
    produto_id uuid NOT NULL,
    quantidade integer NOT NULL,
    preco_unitario_venda numeric(10,2) NOT NULL,
    valor_total_item numeric(10,2) NOT NULL
);


ALTER TABLE public.prest_venda_itens OWNER TO postgres;

--
-- Name: TABLE prest_venda_itens; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_venda_itens IS 'Tabela associativa que detalha os produtos de cada venda.';


--
-- Name: COLUMN prest_venda_itens.venda_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_venda_itens.venda_id IS 'Referência à venda.';


--
-- Name: COLUMN prest_venda_itens.produto_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_venda_itens.produto_id IS 'Referência ao produto vendido.';


--
-- Name: COLUMN prest_venda_itens.preco_unitario_venda; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_venda_itens.preco_unitario_venda IS 'Preço do produto no momento da venda, que pode ser diferente do sugerido.';


--
-- Name: COLUMN prest_venda_itens.valor_total_item; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_venda_itens.valor_total_item IS 'Calculado como (quantidade * preco_unitario_venda).';


--
-- Name: prest_vendas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_vendas (
    id uuid DEFAULT public.uuid_generate_v4() NOT NULL,
    usuario_id uuid NOT NULL,
    cliente_id uuid NOT NULL,
    data_venda timestamp with time zone DEFAULT now() NOT NULL,
    valor_total numeric(10,2) NOT NULL,
    numero_parcelas integer DEFAULT 1 NOT NULL,
    status_venda_codigo character varying(20) DEFAULT 'EM_ABERTO'::character varying NOT NULL,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_vendas OWNER TO postgres;

--
-- Name: TABLE prest_vendas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_vendas IS 'Registra o cabeçalho de cada venda, associando um cliente e um valor total.';


--
-- Name: COLUMN prest_vendas.valor_total; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_vendas.valor_total IS 'Soma total dos itens da venda.';


--
-- Name: COLUMN prest_vendas.numero_parcelas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_vendas.numero_parcelas IS 'Quantidade de parcelas acordadas com o cliente.';


--
-- Name: profile; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.profile (
    user_id integer NOT NULL,
    name character varying(255) DEFAULT NULL::character varying,
    public_email character varying(255) DEFAULT NULL::character varying,
    gravatar_email character varying(255) DEFAULT NULL::character varying,
    gravatar_id character varying(32) DEFAULT NULL::character varying,
    location character varying(255) DEFAULT NULL::character varying,
    website character varying(255) DEFAULT NULL::character varying,
    bio text,
    timezone character varying(40) DEFAULT NULL::character varying
);


ALTER TABLE public.profile OWNER TO postgres;

--
-- Name: servico_adm_contas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_adm_contas (
    id integer NOT NULL,
    nome character varying(150) NOT NULL,
    email character varying(100) NOT NULL,
    senha character varying(255) NOT NULL,
    is_superadmin boolean DEFAULT false
);


ALTER TABLE public.servico_adm_contas OWNER TO postgres;

--
-- Name: TABLE servico_adm_contas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.servico_adm_contas IS 'Administradores do sistema SaaS.';


--
-- Name: servico_adm_contas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_adm_contas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_adm_contas_id_seq OWNER TO postgres;

--
-- Name: servico_adm_contas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_adm_contas_id_seq OWNED BY public.servico_adm_contas.id;


--
-- Name: servico_catalogo_categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_catalogo_categorias (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    ativa boolean DEFAULT true
);


ALTER TABLE public.servico_catalogo_categorias OWNER TO postgres;

--
-- Name: TABLE servico_catalogo_categorias; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.servico_catalogo_categorias IS 'Categorias para organizar os produtos na vitrine/loja virtual.';


--
-- Name: servico_catalogo_categorias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_catalogo_categorias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_catalogo_categorias_id_seq OWNER TO postgres;

--
-- Name: servico_catalogo_categorias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_catalogo_categorias_id_seq OWNED BY public.servico_catalogo_categorias.id;


--
-- Name: servico_catalogo_produto_categoria_assoc; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_catalogo_produto_categoria_assoc (
    produto_id integer NOT NULL,
    categoria_id integer NOT NULL
);


ALTER TABLE public.servico_catalogo_produto_categoria_assoc OWNER TO postgres;

--
-- Name: TABLE servico_catalogo_produto_categoria_assoc; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.servico_catalogo_produto_categoria_assoc IS 'Permite que um produto esteja em várias categorias do catálogo.';


--
-- Name: servico_catalogo_produto_imagens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_catalogo_produto_imagens (
    id integer NOT NULL,
    produto_id integer NOT NULL,
    url_imagem text NOT NULL,
    texto_alternativo character varying(150),
    ordem_exibicao integer DEFAULT 0
);


ALTER TABLE public.servico_catalogo_produto_imagens OWNER TO postgres;

--
-- Name: TABLE servico_catalogo_produto_imagens; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.servico_catalogo_produto_imagens IS 'Armazena múltiplas imagens para cada produto do catálogo.';


--
-- Name: servico_catalogo_produto_imagens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_catalogo_produto_imagens_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_catalogo_produto_imagens_id_seq OWNER TO postgres;

--
-- Name: servico_catalogo_produto_imagens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_catalogo_produto_imagens_id_seq OWNED BY public.servico_catalogo_produto_imagens.id;


--
-- Name: servico_clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_clientes (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    tipo_pessoa_id integer NOT NULL,
    nome_razao_social character varying(150) NOT NULL,
    cpf_cnpj character varying(18)
);


ALTER TABLE public.servico_clientes OWNER TO postgres;

--
-- Name: servico_clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_clientes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_clientes_id_seq OWNER TO postgres;

--
-- Name: servico_clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_clientes_id_seq OWNED BY public.servico_clientes.id;


--
-- Name: servico_contas_pagar; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_contas_pagar (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    terceiro_id integer NOT NULL,
    status_id integer NOT NULL,
    lote_id integer,
    descricao character varying(255) NOT NULL,
    valor numeric(12,2) NOT NULL,
    data_vencimento date NOT NULL
);


ALTER TABLE public.servico_contas_pagar OWNER TO postgres;

--
-- Name: servico_contas_pagar_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_contas_pagar_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_contas_pagar_id_seq OWNER TO postgres;

--
-- Name: servico_contas_pagar_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_contas_pagar_id_seq OWNED BY public.servico_contas_pagar.id;


--
-- Name: servico_contas_receber; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_contas_receber (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    cliente_id integer NOT NULL,
    status_id integer NOT NULL,
    pedido_id integer,
    descricao character varying(255) NOT NULL,
    valor numeric(12,2) NOT NULL,
    data_vencimento date NOT NULL
);


ALTER TABLE public.servico_contas_receber OWNER TO postgres;

--
-- Name: servico_contas_receber_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_contas_receber_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_contas_receber_id_seq OWNER TO postgres;

--
-- Name: servico_contas_receber_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_contas_receber_id_seq OWNED BY public.servico_contas_receber.id;


--
-- Name: servico_empresas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_empresas (
    id integer NOT NULL,
    nome_fantasia character varying(150) NOT NULL,
    razao_social character varying(150),
    cnpj character varying(18),
    email_principal character varying(100) NOT NULL,
    senha character varying(255) NOT NULL,
    data_cadastro timestamp with time zone DEFAULT now() NOT NULL,
    ativo boolean DEFAULT true
);


ALTER TABLE public.servico_empresas OWNER TO postgres;

--
-- Name: TABLE servico_empresas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.servico_empresas IS 'Tabela central de inquilinos (tenants). Cada linha é um cliente do SaaS.';


--
-- Name: servico_empresas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_empresas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_empresas_id_seq OWNER TO postgres;

--
-- Name: servico_empresas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_empresas_id_seq OWNED BY public.servico_empresas.id;


--
-- Name: servico_etapas_producao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_etapas_producao (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    descricao character varying(100) NOT NULL,
    ordem integer NOT NULL
);


ALTER TABLE public.servico_etapas_producao OWNER TO postgres;

--
-- Name: servico_etapas_producao_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_etapas_producao_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_etapas_producao_id_seq OWNER TO postgres;

--
-- Name: servico_etapas_producao_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_etapas_producao_id_seq OWNED BY public.servico_etapas_producao.id;


--
-- Name: servico_ficha_tecnica; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_ficha_tecnica (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    produto_id integer NOT NULL,
    material_id integer NOT NULL,
    quantidade_necessaria numeric(10,4) NOT NULL,
    CONSTRAINT servico_ficha_tecnica_quantidade_necessaria_check CHECK ((quantidade_necessaria > (0)::numeric))
);


ALTER TABLE public.servico_ficha_tecnica OWNER TO postgres;

--
-- Name: servico_ficha_tecnica_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_ficha_tecnica_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_ficha_tecnica_id_seq OWNER TO postgres;

--
-- Name: servico_ficha_tecnica_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_ficha_tecnica_id_seq OWNED BY public.servico_ficha_tecnica.id;


--
-- Name: servico_lotes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_lotes (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    ordem_producao_id integer NOT NULL,
    terceiro_id integer NOT NULL,
    etapa_id integer NOT NULL,
    status_id integer NOT NULL,
    data_envio timestamp without time zone,
    quantidade_enviada integer NOT NULL,
    quantidade_recebida integer,
    quantidade_rejeitada integer,
    valor_servico_unitario numeric(10,2) NOT NULL,
    CONSTRAINT servico_lotes_quantidade_enviada_check CHECK ((quantidade_enviada > 0))
);


ALTER TABLE public.servico_lotes OWNER TO postgres;

--
-- Name: servico_lotes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_lotes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_lotes_id_seq OWNER TO postgres;

--
-- Name: servico_lotes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_lotes_id_seq OWNED BY public.servico_lotes.id;


--
-- Name: servico_materiais; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_materiais (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    ref_material character varying(50) NOT NULL,
    descricao character varying(200) NOT NULL,
    unidade_medida character varying(10) NOT NULL,
    custo_medio numeric(10,4) DEFAULT 0.00
);


ALTER TABLE public.servico_materiais OWNER TO postgres;

--
-- Name: servico_materiais_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_materiais_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_materiais_id_seq OWNER TO postgres;

--
-- Name: servico_materiais_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_materiais_id_seq OWNED BY public.servico_materiais.id;


--
-- Name: servico_movimentacoes_estoque; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_movimentacoes_estoque (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    tipo_movimento_id integer NOT NULL,
    material_id integer,
    produto_id integer,
    data_movimento timestamp without time zone DEFAULT now() NOT NULL,
    quantidade numeric(10,4) NOT NULL,
    observacao text,
    CONSTRAINT chk_item_estoque CHECK ((((material_id IS NOT NULL) AND (produto_id IS NULL)) OR ((material_id IS NULL) AND (produto_id IS NOT NULL))))
);


ALTER TABLE public.servico_movimentacoes_estoque OWNER TO postgres;

--
-- Name: servico_movimentacoes_estoque_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_movimentacoes_estoque_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_movimentacoes_estoque_id_seq OWNER TO postgres;

--
-- Name: servico_movimentacoes_estoque_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_movimentacoes_estoque_id_seq OWNED BY public.servico_movimentacoes_estoque.id;


--
-- Name: servico_ordens_producao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_ordens_producao (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    produto_id integer NOT NULL,
    status_id integer NOT NULL,
    quantidade_planejada integer NOT NULL,
    data_inicio date NOT NULL,
    data_previsao_termino date,
    CONSTRAINT servico_ordens_producao_quantidade_planejada_check CHECK ((quantidade_planejada > 0))
);


ALTER TABLE public.servico_ordens_producao OWNER TO postgres;

--
-- Name: servico_ordens_producao_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_ordens_producao_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_ordens_producao_id_seq OWNER TO postgres;

--
-- Name: servico_ordens_producao_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_ordens_producao_id_seq OWNED BY public.servico_ordens_producao.id;


--
-- Name: servico_pedido_venda_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_pedido_venda_itens (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    pedido_id integer NOT NULL,
    produto_id integer NOT NULL,
    quantidade integer NOT NULL,
    preco_unitario numeric(10,2) NOT NULL
);


ALTER TABLE public.servico_pedido_venda_itens OWNER TO postgres;

--
-- Name: servico_pedido_venda_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_pedido_venda_itens_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_pedido_venda_itens_id_seq OWNER TO postgres;

--
-- Name: servico_pedido_venda_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_pedido_venda_itens_id_seq OWNED BY public.servico_pedido_venda_itens.id;


--
-- Name: servico_pedidos_venda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_pedidos_venda (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    cliente_id integer NOT NULL,
    status_id integer NOT NULL,
    data_pedido timestamp without time zone DEFAULT now() NOT NULL,
    valor_total numeric(12,2) NOT NULL
);


ALTER TABLE public.servico_pedidos_venda OWNER TO postgres;

--
-- Name: servico_pedidos_venda_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_pedidos_venda_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_pedidos_venda_id_seq OWNER TO postgres;

--
-- Name: servico_pedidos_venda_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_pedidos_venda_id_seq OWNED BY public.servico_pedidos_venda.id;


--
-- Name: servico_produtos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_produtos (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    ref_produto character varying(50) NOT NULL,
    descricao character varying(200) NOT NULL,
    preco_venda numeric(10,2) NOT NULL,
    descricao_detalhada text,
    visivel_no_catalogo boolean DEFAULT true NOT NULL,
    produto_destaque boolean DEFAULT false NOT NULL,
    CONSTRAINT servico_produtos_preco_venda_check CHECK ((preco_venda >= (0)::numeric))
);


ALTER TABLE public.servico_produtos OWNER TO postgres;

--
-- Name: COLUMN servico_produtos.descricao_detalhada; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.servico_produtos.descricao_detalhada IS 'Campo para texto longo, HTML ou Markdown para a página do produto.';


--
-- Name: COLUMN servico_produtos.visivel_no_catalogo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.servico_produtos.visivel_no_catalogo IS 'Controla se o produto aparece na loja/catálogo (True/False).';


--
-- Name: COLUMN servico_produtos.produto_destaque; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.servico_produtos.produto_destaque IS 'Marca o produto para aparecer em seções de destaque (True/False).';


--
-- Name: servico_produtos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_produtos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_produtos_id_seq OWNER TO postgres;

--
-- Name: servico_produtos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_produtos_id_seq OWNED BY public.servico_produtos.id;


--
-- Name: servico_qualidade_defeitos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_qualidade_defeitos (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    lote_id integer NOT NULL,
    data_registro date DEFAULT CURRENT_DATE NOT NULL,
    tipo_defeito character varying(100) NOT NULL,
    quantidade integer NOT NULL
);


ALTER TABLE public.servico_qualidade_defeitos OWNER TO postgres;

--
-- Name: servico_qualidade_defeitos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_qualidade_defeitos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_qualidade_defeitos_id_seq OWNER TO postgres;

--
-- Name: servico_qualidade_defeitos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_qualidade_defeitos_id_seq OWNED BY public.servico_qualidade_defeitos.id;


--
-- Name: servico_status_conta_financeira; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_status_conta_financeira (
    id integer NOT NULL,
    descricao character varying(50) NOT NULL
);


ALTER TABLE public.servico_status_conta_financeira OWNER TO postgres;

--
-- Name: servico_status_conta_financeira_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_status_conta_financeira_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_status_conta_financeira_id_seq OWNER TO postgres;

--
-- Name: servico_status_conta_financeira_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_status_conta_financeira_id_seq OWNED BY public.servico_status_conta_financeira.id;


--
-- Name: servico_status_lote; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_status_lote (
    id integer NOT NULL,
    descricao character varying(50) NOT NULL
);


ALTER TABLE public.servico_status_lote OWNER TO postgres;

--
-- Name: servico_status_lote_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_status_lote_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_status_lote_id_seq OWNER TO postgres;

--
-- Name: servico_status_lote_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_status_lote_id_seq OWNED BY public.servico_status_lote.id;


--
-- Name: servico_status_ordem_producao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_status_ordem_producao (
    id integer NOT NULL,
    descricao character varying(50) NOT NULL
);


ALTER TABLE public.servico_status_ordem_producao OWNER TO postgres;

--
-- Name: servico_status_ordem_producao_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_status_ordem_producao_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_status_ordem_producao_id_seq OWNER TO postgres;

--
-- Name: servico_status_ordem_producao_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_status_ordem_producao_id_seq OWNED BY public.servico_status_ordem_producao.id;


--
-- Name: servico_status_pedido_venda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_status_pedido_venda (
    id integer NOT NULL,
    descricao character varying(50) NOT NULL
);


ALTER TABLE public.servico_status_pedido_venda OWNER TO postgres;

--
-- Name: servico_status_pedido_venda_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_status_pedido_venda_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_status_pedido_venda_id_seq OWNER TO postgres;

--
-- Name: servico_status_pedido_venda_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_status_pedido_venda_id_seq OWNED BY public.servico_status_pedido_venda.id;


--
-- Name: servico_terceiros; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_terceiros (
    id integer NOT NULL,
    empresa_id integer NOT NULL,
    tipo_pessoa_id integer NOT NULL,
    nome_razao_social character varying(150) NOT NULL,
    cpf_cnpj character varying(18),
    telefone character varying(20),
    email character varying(100)
);


ALTER TABLE public.servico_terceiros OWNER TO postgres;

--
-- Name: servico_terceiros_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_terceiros_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_terceiros_id_seq OWNER TO postgres;

--
-- Name: servico_terceiros_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_terceiros_id_seq OWNED BY public.servico_terceiros.id;


--
-- Name: servico_tipos_movimento_estoque; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_tipos_movimento_estoque (
    id integer NOT NULL,
    descricao character varying(50) NOT NULL,
    fator integer NOT NULL
);


ALTER TABLE public.servico_tipos_movimento_estoque OWNER TO postgres;

--
-- Name: COLUMN servico_tipos_movimento_estoque.fator; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.servico_tipos_movimento_estoque.fator IS 'Define se o movimento adiciona ou remove do estoque.';


--
-- Name: servico_tipos_movimento_estoque_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_tipos_movimento_estoque_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_tipos_movimento_estoque_id_seq OWNER TO postgres;

--
-- Name: servico_tipos_movimento_estoque_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_tipos_movimento_estoque_id_seq OWNED BY public.servico_tipos_movimento_estoque.id;


--
-- Name: servico_tipos_pessoa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.servico_tipos_pessoa (
    id integer NOT NULL,
    descricao character varying(50) NOT NULL
);


ALTER TABLE public.servico_tipos_pessoa OWNER TO postgres;

--
-- Name: servico_tipos_pessoa_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.servico_tipos_pessoa_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.servico_tipos_pessoa_id_seq OWNER TO postgres;

--
-- Name: servico_tipos_pessoa_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.servico_tipos_pessoa_id_seq OWNED BY public.servico_tipos_pessoa.id;


--
-- Name: sys_modulos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sys_modulos (
    id integer NOT NULL,
    modulo character varying(250) NOT NULL,
    path character varying(250) NOT NULL,
    status boolean DEFAULT false NOT NULL
);


ALTER TABLE public.sys_modulos OWNER TO postgres;

--
-- Name: sys_modulos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.sys_modulos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.sys_modulos_id_seq OWNER TO postgres;

--
-- Name: sys_modulos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.sys_modulos_id_seq OWNED BY public.sys_modulos.id;


--
-- Name: tab_form_login; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.tab_form_login (
    id bigint NOT NULL,
    estabelecimento_cnes text,
    usuario_id bigint,
    usuario text,
    senha text,
    local_de_trabalho_id integer,
    setor_de_trabalho_id integer,
    created_at timestamp without time zone,
    created_by integer,
    updated_at timestamp without time zone,
    updated_by integer,
    latitude character varying(50),
    longitude character varying(50),
    altitude character varying(50),
    user_ip character varying(200),
    logado boolean DEFAULT false,
    fila_unidade_id integer
);


ALTER TABLE public.tab_form_login OWNER TO postgres;

--
-- Name: tab_form_login_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.tab_form_login_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tab_form_login_id_seq OWNER TO postgres;

--
-- Name: tab_form_login_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.tab_form_login_id_seq OWNED BY public.tab_form_login.id;


--
-- Name: token; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.token (
    user_id integer NOT NULL,
    code character varying(32) NOT NULL,
    created_at integer NOT NULL,
    type smallint NOT NULL
);


ALTER TABLE public.token OWNER TO postgres;

--
-- Name: user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public."user" (
    id integer NOT NULL,
    username character varying(25) NOT NULL,
    email character varying(255) NOT NULL,
    password_hash character varying(60) NOT NULL,
    auth_key character varying(32) NOT NULL,
    confirmed_at integer,
    unconfirmed_email character varying(255) DEFAULT NULL::character varying,
    blocked_at integer,
    registration_ip character varying(45),
    created_at integer NOT NULL,
    updated_at integer NOT NULL,
    flags integer DEFAULT 0 NOT NULL,
    last_login_at integer
);


ALTER TABLE public."user" OWNER TO postgres;

--
-- Name: user_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_id_seq OWNER TO postgres;

--
-- Name: user_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_id_seq OWNED BY public."user".id;


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
-- Name: ind_atributos_qualidade_desempenho id_atributo_qd; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_atributos_qualidade_desempenho ALTER COLUMN id_atributo_qd SET DEFAULT nextval('public.ind_atributos_qualidade_desempenho_id_atributo_qd_seq'::regclass);


--
-- Name: ind_categorias_desagregacao id_categoria_desagregacao; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_categorias_desagregacao ALTER COLUMN id_categoria_desagregacao SET DEFAULT nextval('public.ind_categorias_desagregacao_id_categoria_desagregacao_seq'::regclass);


--
-- Name: ind_definicoes_indicadores id_indicador; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores ALTER COLUMN id_indicador SET DEFAULT nextval('public.ind_definicoes_indicadores_id_indicador_seq'::regclass);


--
-- Name: ind_dimensoes_indicadores id_dimensao; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_dimensoes_indicadores ALTER COLUMN id_dimensao SET DEFAULT nextval('public.ind_dimensoes_indicadores_id_dimensao_seq'::regclass);


--
-- Name: ind_fontes_dados id_fonte; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_fontes_dados ALTER COLUMN id_fonte SET DEFAULT nextval('public.ind_fontes_dados_id_fonte_seq'::regclass);


--
-- Name: ind_metas_indicadores id_meta; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_metas_indicadores ALTER COLUMN id_meta SET DEFAULT nextval('public.ind_metas_indicadores_id_meta_seq'::regclass);


--
-- Name: ind_niveis_abrangencia id_nivel_abrangencia; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_niveis_abrangencia ALTER COLUMN id_nivel_abrangencia SET DEFAULT nextval('public.ind_niveis_abrangencia_id_nivel_abrangencia_seq'::regclass);


--
-- Name: ind_opcoes_desagregacao id_opcao_desagregacao; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_opcoes_desagregacao ALTER COLUMN id_opcao_desagregacao SET DEFAULT nextval('public.ind_opcoes_desagregacao_id_opcao_desagregacao_seq'::regclass);


--
-- Name: ind_periodicidades id_periodicidade; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_periodicidades ALTER COLUMN id_periodicidade SET DEFAULT nextval('public.ind_periodicidades_id_periodicidade_seq'::regclass);


--
-- Name: ind_relacoes_indicadores id_relacao; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_relacoes_indicadores ALTER COLUMN id_relacao SET DEFAULT nextval('public.ind_relacoes_indicadores_id_relacao_seq'::regclass);


--
-- Name: ind_unidades_medida id_unidade; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_unidades_medida ALTER COLUMN id_unidade SET DEFAULT nextval('public.ind_unidades_medida_id_unidade_seq'::regclass);


--
-- Name: ind_valores_indicadores id_valor; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores ALTER COLUMN id_valor SET DEFAULT nextval('public.ind_valores_indicadores_id_valor_seq'::regclass);


--
-- Name: indica_producao_diaria id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_producao_diaria ALTER COLUMN id SET DEFAULT nextval('public.indica_producao_diaria_id_seq'::regclass);


--
-- Name: indica_qualidade_defeitos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_qualidade_defeitos ALTER COLUMN id SET DEFAULT nextval('public.indica_qualidade_defeitos_id_seq'::regclass);


--
-- Name: indica_tempos_producao id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_tempos_producao ALTER COLUMN id SET DEFAULT nextval('public.indica_tempos_producao_id_seq'::regclass);


--
-- Name: servico_adm_contas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_adm_contas ALTER COLUMN id SET DEFAULT nextval('public.servico_adm_contas_id_seq'::regclass);


--
-- Name: servico_catalogo_categorias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_categorias ALTER COLUMN id SET DEFAULT nextval('public.servico_catalogo_categorias_id_seq'::regclass);


--
-- Name: servico_catalogo_produto_imagens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_produto_imagens ALTER COLUMN id SET DEFAULT nextval('public.servico_catalogo_produto_imagens_id_seq'::regclass);


--
-- Name: servico_clientes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_clientes ALTER COLUMN id SET DEFAULT nextval('public.servico_clientes_id_seq'::regclass);


--
-- Name: servico_contas_pagar id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar ALTER COLUMN id SET DEFAULT nextval('public.servico_contas_pagar_id_seq'::regclass);


--
-- Name: servico_contas_receber id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber ALTER COLUMN id SET DEFAULT nextval('public.servico_contas_receber_id_seq'::regclass);


--
-- Name: servico_empresas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_empresas ALTER COLUMN id SET DEFAULT nextval('public.servico_empresas_id_seq'::regclass);


--
-- Name: servico_etapas_producao id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_etapas_producao ALTER COLUMN id SET DEFAULT nextval('public.servico_etapas_producao_id_seq'::regclass);


--
-- Name: servico_ficha_tecnica id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ficha_tecnica ALTER COLUMN id SET DEFAULT nextval('public.servico_ficha_tecnica_id_seq'::regclass);


--
-- Name: servico_lotes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes ALTER COLUMN id SET DEFAULT nextval('public.servico_lotes_id_seq'::regclass);


--
-- Name: servico_materiais id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_materiais ALTER COLUMN id SET DEFAULT nextval('public.servico_materiais_id_seq'::regclass);


--
-- Name: servico_movimentacoes_estoque id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_movimentacoes_estoque ALTER COLUMN id SET DEFAULT nextval('public.servico_movimentacoes_estoque_id_seq'::regclass);


--
-- Name: servico_ordens_producao id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ordens_producao ALTER COLUMN id SET DEFAULT nextval('public.servico_ordens_producao_id_seq'::regclass);


--
-- Name: servico_pedido_venda_itens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedido_venda_itens ALTER COLUMN id SET DEFAULT nextval('public.servico_pedido_venda_itens_id_seq'::regclass);


--
-- Name: servico_pedidos_venda id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedidos_venda ALTER COLUMN id SET DEFAULT nextval('public.servico_pedidos_venda_id_seq'::regclass);


--
-- Name: servico_produtos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_produtos ALTER COLUMN id SET DEFAULT nextval('public.servico_produtos_id_seq'::regclass);


--
-- Name: servico_qualidade_defeitos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_qualidade_defeitos ALTER COLUMN id SET DEFAULT nextval('public.servico_qualidade_defeitos_id_seq'::regclass);


--
-- Name: servico_status_conta_financeira id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_conta_financeira ALTER COLUMN id SET DEFAULT nextval('public.servico_status_conta_financeira_id_seq'::regclass);


--
-- Name: servico_status_lote id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_lote ALTER COLUMN id SET DEFAULT nextval('public.servico_status_lote_id_seq'::regclass);


--
-- Name: servico_status_ordem_producao id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_ordem_producao ALTER COLUMN id SET DEFAULT nextval('public.servico_status_ordem_producao_id_seq'::regclass);


--
-- Name: servico_status_pedido_venda id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_pedido_venda ALTER COLUMN id SET DEFAULT nextval('public.servico_status_pedido_venda_id_seq'::regclass);


--
-- Name: servico_terceiros id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_terceiros ALTER COLUMN id SET DEFAULT nextval('public.servico_terceiros_id_seq'::regclass);


--
-- Name: servico_tipos_movimento_estoque id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_tipos_movimento_estoque ALTER COLUMN id SET DEFAULT nextval('public.servico_tipos_movimento_estoque_id_seq'::regclass);


--
-- Name: servico_tipos_pessoa id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_tipos_pessoa ALTER COLUMN id SET DEFAULT nextval('public.servico_tipos_pessoa_id_seq'::regclass);


--
-- Name: social_account id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.social_account ALTER COLUMN id SET DEFAULT nextval('public.account_id_seq'::regclass);


--
-- Name: sys_modulos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sys_modulos ALTER COLUMN id SET DEFAULT nextval('public.sys_modulos_id_seq'::regclass);


--
-- Name: tab_form_login id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tab_form_login ALTER COLUMN id SET DEFAULT nextval('public.tab_form_login_id_seq'::regclass);


--
-- Name: user id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."user" ALTER COLUMN id SET DEFAULT nextval('public.user_id_seq'::regclass);


--
-- Name: social_account account_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.social_account
    ADD CONSTRAINT account_pkey PRIMARY KEY (id);


--
-- Name: auth_assignment auth_assignment_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_assignment
    ADD CONSTRAINT auth_assignment_pkey PRIMARY KEY (item_name, user_id);


--
-- Name: auth_item_child auth_item_child_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_item_child
    ADD CONSTRAINT auth_item_child_pkey PRIMARY KEY (parent, child);


--
-- Name: auth_item auth_item_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_item
    ADD CONSTRAINT auth_item_pkey PRIMARY KEY (name);


--
-- Name: auth_rule auth_rule_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_rule
    ADD CONSTRAINT auth_rule_pkey PRIMARY KEY (name);


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
-- Name: tab_form_login idx_58317_primary; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.tab_form_login
    ADD CONSTRAINT idx_58317_primary PRIMARY KEY (id);


--
-- Name: ind_atributos_qualidade_desempenho ind_atributos_qualidade_desempenho_id_indicador_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_atributos_qualidade_desempenho
    ADD CONSTRAINT ind_atributos_qualidade_desempenho_id_indicador_key UNIQUE (id_indicador);


--
-- Name: ind_atributos_qualidade_desempenho ind_atributos_qualidade_desempenho_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_atributos_qualidade_desempenho
    ADD CONSTRAINT ind_atributos_qualidade_desempenho_pkey PRIMARY KEY (id_atributo_qd);


--
-- Name: ind_categorias_desagregacao ind_categorias_desagregacao_nome_categoria_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_categorias_desagregacao
    ADD CONSTRAINT ind_categorias_desagregacao_nome_categoria_key UNIQUE (nome_categoria);


--
-- Name: ind_categorias_desagregacao ind_categorias_desagregacao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_categorias_desagregacao
    ADD CONSTRAINT ind_categorias_desagregacao_pkey PRIMARY KEY (id_categoria_desagregacao);


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_cod_indicador_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_cod_indicador_key UNIQUE (cod_indicador);


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_pkey PRIMARY KEY (id_indicador);


--
-- Name: ind_dimensoes_indicadores ind_dimensoes_indicadores_nome_dimensao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_dimensoes_indicadores
    ADD CONSTRAINT ind_dimensoes_indicadores_nome_dimensao_key UNIQUE (nome_dimensao);


--
-- Name: ind_dimensoes_indicadores ind_dimensoes_indicadores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_dimensoes_indicadores
    ADD CONSTRAINT ind_dimensoes_indicadores_pkey PRIMARY KEY (id_dimensao);


--
-- Name: ind_fontes_dados ind_fontes_dados_nome_fonte_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_fontes_dados
    ADD CONSTRAINT ind_fontes_dados_nome_fonte_key UNIQUE (nome_fonte);


--
-- Name: ind_fontes_dados ind_fontes_dados_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_fontes_dados
    ADD CONSTRAINT ind_fontes_dados_pkey PRIMARY KEY (id_fonte);


--
-- Name: ind_metas_indicadores ind_metas_indicadores_id_indicador_tipo_de_meta_data_inicio_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_metas_indicadores
    ADD CONSTRAINT ind_metas_indicadores_id_indicador_tipo_de_meta_data_inicio_key UNIQUE (id_indicador, tipo_de_meta, data_inicio_vigencia, id_nivel_abrangencia_aplicavel, valor_meta_referencia_1);


--
-- Name: ind_metas_indicadores ind_metas_indicadores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_metas_indicadores
    ADD CONSTRAINT ind_metas_indicadores_pkey PRIMARY KEY (id_meta);


--
-- Name: ind_niveis_abrangencia ind_niveis_abrangencia_nome_nivel_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_niveis_abrangencia
    ADD CONSTRAINT ind_niveis_abrangencia_nome_nivel_key UNIQUE (nome_nivel);


--
-- Name: ind_niveis_abrangencia ind_niveis_abrangencia_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_niveis_abrangencia
    ADD CONSTRAINT ind_niveis_abrangencia_pkey PRIMARY KEY (id_nivel_abrangencia);


--
-- Name: ind_opcoes_desagregacao ind_opcoes_desagregacao_id_categoria_desagregacao_valor_opc_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_opcoes_desagregacao
    ADD CONSTRAINT ind_opcoes_desagregacao_id_categoria_desagregacao_valor_opc_key UNIQUE (id_categoria_desagregacao, valor_opcao);


--
-- Name: ind_opcoes_desagregacao ind_opcoes_desagregacao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_opcoes_desagregacao
    ADD CONSTRAINT ind_opcoes_desagregacao_pkey PRIMARY KEY (id_opcao_desagregacao);


--
-- Name: ind_periodicidades ind_periodicidades_nome_periodicidade_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_periodicidades
    ADD CONSTRAINT ind_periodicidades_nome_periodicidade_key UNIQUE (nome_periodicidade);


--
-- Name: ind_periodicidades ind_periodicidades_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_periodicidades
    ADD CONSTRAINT ind_periodicidades_pkey PRIMARY KEY (id_periodicidade);


--
-- Name: ind_relacoes_indicadores ind_relacoes_indicadores_id_indicador_origem_id_indicador_d_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_relacoes_indicadores
    ADD CONSTRAINT ind_relacoes_indicadores_id_indicador_origem_id_indicador_d_key UNIQUE (id_indicador_origem, id_indicador_destino, tipo_relacao);


--
-- Name: ind_relacoes_indicadores ind_relacoes_indicadores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_relacoes_indicadores
    ADD CONSTRAINT ind_relacoes_indicadores_pkey PRIMARY KEY (id_relacao);


--
-- Name: ind_unidades_medida ind_unidades_medida_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_unidades_medida
    ADD CONSTRAINT ind_unidades_medida_pkey PRIMARY KEY (id_unidade);


--
-- Name: ind_unidades_medida ind_unidades_medida_sigla_unidade_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_unidades_medida
    ADD CONSTRAINT ind_unidades_medida_sigla_unidade_key UNIQUE (sigla_unidade);


--
-- Name: ind_valores_indicadores_desagregacoes ind_valores_indicadores_desagregacoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores_desagregacoes
    ADD CONSTRAINT ind_valores_indicadores_desagregacoes_pkey PRIMARY KEY (id_valor_indicador, id_opcao_desagregacao);


--
-- Name: ind_valores_indicadores ind_valores_indicadores_id_indicador_data_referencia_id_niv_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores
    ADD CONSTRAINT ind_valores_indicadores_id_indicador_data_referencia_id_niv_key UNIQUE (id_indicador, data_referencia, id_nivel_abrangencia, codigo_especifico_abrangencia);


--
-- Name: ind_valores_indicadores ind_valores_indicadores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores
    ADD CONSTRAINT ind_valores_indicadores_pkey PRIMARY KEY (id_valor);


--
-- Name: indica_producao_diaria indica_producao_diaria_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_producao_diaria
    ADD CONSTRAINT indica_producao_diaria_pkey PRIMARY KEY (id);


--
-- Name: indica_producao_diaria indica_producao_diaria_terceiro_id_data_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_producao_diaria
    ADD CONSTRAINT indica_producao_diaria_terceiro_id_data_key UNIQUE (terceiro_id, data);


--
-- Name: indica_qualidade_defeitos indica_qualidade_defeitos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_qualidade_defeitos
    ADD CONSTRAINT indica_qualidade_defeitos_pkey PRIMARY KEY (id);


--
-- Name: indica_tempos_producao indica_tempos_producao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.indica_tempos_producao
    ADD CONSTRAINT indica_tempos_producao_pkey PRIMARY KEY (id);


--
-- Name: many_sys_modulos_has_many_ind_dimensoes_indicadores many_sys_modulos_has_many_ind_dimensoes_indicadores_pk; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_ind_dimensoes_indicadores
    ADD CONSTRAINT many_sys_modulos_has_many_ind_dimensoes_indicadores_pk PRIMARY KEY (id_sys_modulos, id_dimensao_ind_dimensoes_indicadores);


--
-- Name: many_sys_modulos_has_many_user many_sys_modulos_has_many_user_pk; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_user
    ADD CONSTRAINT many_sys_modulos_has_many_user_pk PRIMARY KEY (id_sys_modulos, id_user);


--
-- Name: migration migration_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migration
    ADD CONSTRAINT migration_pkey PRIMARY KEY (version);


--
-- Name: prest_clientes prest_clientes_cpf_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_cpf_key UNIQUE (cpf);


--
-- Name: prest_clientes prest_clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_pkey PRIMARY KEY (id);


--
-- Name: prest_parcelas prest_parcelas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_pkey PRIMARY KEY (id);


--
-- Name: prest_produtos prest_produtos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produtos
    ADD CONSTRAINT prest_produtos_pkey PRIMARY KEY (id);


--
-- Name: prest_produtos prest_produtos_usuario_id_codigo_referencia_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produtos
    ADD CONSTRAINT prest_produtos_usuario_id_codigo_referencia_key UNIQUE (usuario_id, codigo_referencia);


--
-- Name: prest_status_parcela prest_status_parcela_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_status_parcela
    ADD CONSTRAINT prest_status_parcela_pkey PRIMARY KEY (codigo);


--
-- Name: prest_status_venda prest_status_venda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_status_venda
    ADD CONSTRAINT prest_status_venda_pkey PRIMARY KEY (codigo);


--
-- Name: prest_usuarios prest_usuarios_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_usuarios
    ADD CONSTRAINT prest_usuarios_email_key UNIQUE (email);


--
-- Name: prest_usuarios prest_usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_usuarios
    ADD CONSTRAINT prest_usuarios_pkey PRIMARY KEY (id);


--
-- Name: prest_venda_itens prest_venda_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_venda_itens
    ADD CONSTRAINT prest_venda_itens_pkey PRIMARY KEY (id);


--
-- Name: prest_vendas prest_vendas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendas
    ADD CONSTRAINT prest_vendas_pkey PRIMARY KEY (id);


--
-- Name: profile profile_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profile
    ADD CONSTRAINT profile_pkey PRIMARY KEY (user_id);


--
-- Name: servico_adm_contas servico_adm_contas_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_adm_contas
    ADD CONSTRAINT servico_adm_contas_email_key UNIQUE (email);


--
-- Name: servico_adm_contas servico_adm_contas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_adm_contas
    ADD CONSTRAINT servico_adm_contas_pkey PRIMARY KEY (id);


--
-- Name: servico_catalogo_categorias servico_catalogo_categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_categorias
    ADD CONSTRAINT servico_catalogo_categorias_pkey PRIMARY KEY (id);


--
-- Name: servico_catalogo_produto_categoria_assoc servico_catalogo_produto_categoria_assoc_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_produto_categoria_assoc
    ADD CONSTRAINT servico_catalogo_produto_categoria_assoc_pkey PRIMARY KEY (produto_id, categoria_id);


--
-- Name: servico_catalogo_produto_imagens servico_catalogo_produto_imagens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_produto_imagens
    ADD CONSTRAINT servico_catalogo_produto_imagens_pkey PRIMARY KEY (id);


--
-- Name: servico_clientes servico_clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_clientes
    ADD CONSTRAINT servico_clientes_pkey PRIMARY KEY (id);


--
-- Name: servico_contas_pagar servico_contas_pagar_lote_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar
    ADD CONSTRAINT servico_contas_pagar_lote_id_key UNIQUE (lote_id);


--
-- Name: servico_contas_pagar servico_contas_pagar_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar
    ADD CONSTRAINT servico_contas_pagar_pkey PRIMARY KEY (id);


--
-- Name: servico_contas_receber servico_contas_receber_pedido_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber
    ADD CONSTRAINT servico_contas_receber_pedido_id_key UNIQUE (pedido_id);


--
-- Name: servico_contas_receber servico_contas_receber_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber
    ADD CONSTRAINT servico_contas_receber_pkey PRIMARY KEY (id);


--
-- Name: servico_empresas servico_empresas_cnpj_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_empresas
    ADD CONSTRAINT servico_empresas_cnpj_key UNIQUE (cnpj);


--
-- Name: servico_empresas servico_empresas_email_principal_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_empresas
    ADD CONSTRAINT servico_empresas_email_principal_key UNIQUE (email_principal);


--
-- Name: servico_empresas servico_empresas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_empresas
    ADD CONSTRAINT servico_empresas_pkey PRIMARY KEY (id);


--
-- Name: servico_etapas_producao servico_etapas_producao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_etapas_producao
    ADD CONSTRAINT servico_etapas_producao_pkey PRIMARY KEY (id);


--
-- Name: servico_ficha_tecnica servico_ficha_tecnica_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ficha_tecnica
    ADD CONSTRAINT servico_ficha_tecnica_pkey PRIMARY KEY (id);


--
-- Name: servico_lotes servico_lotes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes
    ADD CONSTRAINT servico_lotes_pkey PRIMARY KEY (id);


--
-- Name: servico_materiais servico_materiais_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_materiais
    ADD CONSTRAINT servico_materiais_pkey PRIMARY KEY (id);


--
-- Name: servico_movimentacoes_estoque servico_movimentacoes_estoque_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_movimentacoes_estoque
    ADD CONSTRAINT servico_movimentacoes_estoque_pkey PRIMARY KEY (id);


--
-- Name: servico_ordens_producao servico_ordens_producao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ordens_producao
    ADD CONSTRAINT servico_ordens_producao_pkey PRIMARY KEY (id);


--
-- Name: servico_pedido_venda_itens servico_pedido_venda_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedido_venda_itens
    ADD CONSTRAINT servico_pedido_venda_itens_pkey PRIMARY KEY (id);


--
-- Name: servico_pedidos_venda servico_pedidos_venda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedidos_venda
    ADD CONSTRAINT servico_pedidos_venda_pkey PRIMARY KEY (id);


--
-- Name: servico_produtos servico_produtos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_produtos
    ADD CONSTRAINT servico_produtos_pkey PRIMARY KEY (id);


--
-- Name: servico_qualidade_defeitos servico_qualidade_defeitos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_qualidade_defeitos
    ADD CONSTRAINT servico_qualidade_defeitos_pkey PRIMARY KEY (id);


--
-- Name: servico_status_conta_financeira servico_status_conta_financeira_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_conta_financeira
    ADD CONSTRAINT servico_status_conta_financeira_descricao_key UNIQUE (descricao);


--
-- Name: servico_status_conta_financeira servico_status_conta_financeira_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_conta_financeira
    ADD CONSTRAINT servico_status_conta_financeira_pkey PRIMARY KEY (id);


--
-- Name: servico_status_lote servico_status_lote_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_lote
    ADD CONSTRAINT servico_status_lote_descricao_key UNIQUE (descricao);


--
-- Name: servico_status_lote servico_status_lote_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_lote
    ADD CONSTRAINT servico_status_lote_pkey PRIMARY KEY (id);


--
-- Name: servico_status_ordem_producao servico_status_ordem_producao_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_ordem_producao
    ADD CONSTRAINT servico_status_ordem_producao_descricao_key UNIQUE (descricao);


--
-- Name: servico_status_ordem_producao servico_status_ordem_producao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_ordem_producao
    ADD CONSTRAINT servico_status_ordem_producao_pkey PRIMARY KEY (id);


--
-- Name: servico_status_pedido_venda servico_status_pedido_venda_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_pedido_venda
    ADD CONSTRAINT servico_status_pedido_venda_descricao_key UNIQUE (descricao);


--
-- Name: servico_status_pedido_venda servico_status_pedido_venda_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_status_pedido_venda
    ADD CONSTRAINT servico_status_pedido_venda_pkey PRIMARY KEY (id);


--
-- Name: servico_terceiros servico_terceiros_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_terceiros
    ADD CONSTRAINT servico_terceiros_pkey PRIMARY KEY (id);


--
-- Name: servico_tipos_movimento_estoque servico_tipos_movimento_estoque_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_tipos_movimento_estoque
    ADD CONSTRAINT servico_tipos_movimento_estoque_descricao_key UNIQUE (descricao);


--
-- Name: servico_tipos_movimento_estoque servico_tipos_movimento_estoque_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_tipos_movimento_estoque
    ADD CONSTRAINT servico_tipos_movimento_estoque_pkey PRIMARY KEY (id);


--
-- Name: servico_tipos_pessoa servico_tipos_pessoa_descricao_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_tipos_pessoa
    ADD CONSTRAINT servico_tipos_pessoa_descricao_key UNIQUE (descricao);


--
-- Name: servico_tipos_pessoa servico_tipos_pessoa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_tipos_pessoa
    ADD CONSTRAINT servico_tipos_pessoa_pkey PRIMARY KEY (id);


--
-- Name: sys_modulos sys_modulos_pk; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sys_modulos
    ADD CONSTRAINT sys_modulos_pk PRIMARY KEY (id);


--
-- Name: servico_catalogo_categorias uq_catalogo_categoria_nome; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_categorias
    ADD CONSTRAINT uq_catalogo_categoria_nome UNIQUE (empresa_id, nome);


--
-- Name: delivery_categorias uq_categoria_nome_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_categorias
    ADD CONSTRAINT uq_categoria_nome_estabelecimento UNIQUE (estabelecimento_id, nome);


--
-- Name: servico_clientes uq_cliente_documento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_clientes
    ADD CONSTRAINT uq_cliente_documento UNIQUE (empresa_id, cpf_cnpj);


--
-- Name: delivery_clientes uq_cliente_telefone; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_clientes
    ADD CONSTRAINT uq_cliente_telefone UNIQUE (telefone);


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
-- Name: servico_etapas_producao uq_etapa_descricao; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_etapas_producao
    ADD CONSTRAINT uq_etapa_descricao UNIQUE (empresa_id, descricao);


--
-- Name: servico_ficha_tecnica uq_ficha_tecnica_item; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ficha_tecnica
    ADD CONSTRAINT uq_ficha_tecnica_item UNIQUE (empresa_id, produto_id, material_id);


--
-- Name: servico_materiais uq_material_ref; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_materiais
    ADD CONSTRAINT uq_material_ref UNIQUE (empresa_id, ref_material);


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
-- Name: servico_produtos uq_produto_ref; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_produtos
    ADD CONSTRAINT uq_produto_ref UNIQUE (empresa_id, ref_produto);


--
-- Name: delivery_uso_promocoes uq_promocao_pedido; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_uso_promocoes
    ADD CONSTRAINT uq_promocao_pedido UNIQUE (promocao_id, pedido_id);


--
-- Name: servico_terceiros uq_terceiro_documento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_terceiros
    ADD CONSTRAINT uq_terceiro_documento UNIQUE (empresa_id, cpf_cnpj);


--
-- Name: delivery_usuarios_estabelecimento uq_usuario_email_estabelecimento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.delivery_usuarios_estabelecimento
    ADD CONSTRAINT uq_usuario_email_estabelecimento UNIQUE (estabelecimento_id, email);


--
-- Name: user user_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public."user"
    ADD CONSTRAINT user_pkey PRIMARY KEY (id);


--
-- Name: account_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX account_unique ON public.social_account USING btree (provider, client_id);


--
-- Name: account_unique_code; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX account_unique_code ON public.social_account USING btree (code);


--
-- Name: auth_assignment_user_id_idx; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX auth_assignment_user_id_idx ON public.auth_assignment USING btree (user_id);


--
-- Name: fki_fk_tab_form_login_has_fila_locais_atendimento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX fki_fk_tab_form_login_has_fila_locais_atendimento ON public.tab_form_login USING btree (local_de_trabalho_id);


--
-- Name: fki_fk_tab_form_login_has_fila_sublocais_atendimento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX fki_fk_tab_form_login_has_fila_sublocais_atendimento ON public.tab_form_login USING btree (setor_de_trabalho_id);


--
-- Name: idx-auth_item-type; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX "idx-auth_item-type" ON public.auth_item USING btree (type);


--
-- Name: idx_clientes_nome; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_nome ON public.prest_clientes USING btree (nome_completo);


--
-- Name: idx_clientes_telefone; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_telefone ON public.delivery_clientes USING btree (telefone);


--
-- Name: idx_clientes_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_usuario_id ON public.prest_clientes USING btree (usuario_id);


--
-- Name: idx_definicoes_cod_indicador; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_definicoes_cod_indicador ON public.ind_definicoes_indicadores USING btree (cod_indicador);


--
-- Name: idx_definicoes_id_dimensao; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_definicoes_id_dimensao ON public.ind_definicoes_indicadores USING btree (id_dimensao);


--
-- Name: idx_definicoes_nome_indicador; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_definicoes_nome_indicador ON public.ind_definicoes_indicadores USING gin (to_tsvector('portuguese'::regconfig, (nome_indicador)::text));


--
-- Name: idx_definicoes_tipo_especifico; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_definicoes_tipo_especifico ON public.ind_definicoes_indicadores USING btree (tipo_especifico);


--
-- Name: idx_enderecos_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_enderecos_cliente ON public.delivery_enderecos_cliente USING btree (cliente_id);


--
-- Name: idx_estabelecimentos_nome_fulltext; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_estabelecimentos_nome_fulltext ON public.delivery_estabelecimentos USING gin (to_tsvector('portuguese'::regconfig, (nome_fantasia)::text));


--
-- Name: idx_metas_data_inicio_vigencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_metas_data_inicio_vigencia ON public.ind_metas_indicadores USING btree (data_inicio_vigencia);


--
-- Name: idx_metas_id_indicador; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_metas_id_indicador ON public.ind_metas_indicadores USING btree (id_indicador);


--
-- Name: idx_movimentacoes_estabelecimento_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_movimentacoes_estabelecimento_data ON public.delivery_movimentacoes_financeiras USING btree (estabelecimento_id, data_movimento);


--
-- Name: idx_opcoes_desagregacao_id_categoria; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_opcoes_desagregacao_id_categoria ON public.ind_opcoes_desagregacao USING btree (id_categoria_desagregacao);


--
-- Name: idx_parcelas_data_vencimento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_parcelas_data_vencimento ON public.prest_parcelas USING btree (data_vencimento);


--
-- Name: idx_parcelas_status_codigo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_parcelas_status_codigo ON public.prest_parcelas USING btree (status_parcela_codigo);


--
-- Name: idx_parcelas_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_parcelas_usuario_id ON public.prest_parcelas USING btree (usuario_id);


--
-- Name: idx_parcelas_venda_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_parcelas_venda_id ON public.prest_parcelas USING btree (venda_id);


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
-- Name: idx_produtos_nome; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_nome ON public.prest_produtos USING btree (nome);


--
-- Name: idx_produtos_nome_fulltext; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_nome_fulltext ON public.delivery_produtos USING gin (to_tsvector('portuguese'::regconfig, (nome)::text));


--
-- Name: idx_produtos_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_usuario_id ON public.prest_produtos USING btree (usuario_id);


--
-- Name: idx_valores_codigo_especifico_abrangencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_valores_codigo_especifico_abrangencia ON public.ind_valores_indicadores USING btree (codigo_especifico_abrangencia);


--
-- Name: idx_valores_data_referencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_valores_data_referencia ON public.ind_valores_indicadores USING btree (data_referencia DESC);


--
-- Name: idx_valores_id_indicador; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_valores_id_indicador ON public.ind_valores_indicadores USING btree (id_indicador);


--
-- Name: idx_valores_id_nivel_abrangencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_valores_id_nivel_abrangencia ON public.ind_valores_indicadores USING btree (id_nivel_abrangencia);


--
-- Name: idx_venda_itens_produto_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_venda_itens_produto_id ON public.prest_venda_itens USING btree (produto_id);


--
-- Name: idx_venda_itens_venda_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_venda_itens_venda_id ON public.prest_venda_itens USING btree (venda_id);


--
-- Name: idx_vendas_cliente_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_cliente_id ON public.prest_vendas USING btree (cliente_id);


--
-- Name: idx_vendas_status_codigo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_status_codigo ON public.prest_vendas USING btree (status_venda_codigo);


--
-- Name: idx_vendas_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_usuario_id ON public.prest_vendas USING btree (usuario_id);


--
-- Name: token_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX token_unique ON public.token USING btree (user_id, code, type);


--
-- Name: user_unique_email; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_unique_email ON public."user" USING btree (email);


--
-- Name: user_unique_username; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_unique_username ON public."user" USING btree (username);


--
-- Name: prest_clientes set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_clientes FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: prest_produtos set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_produtos FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: prest_usuarios set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_usuarios FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: prest_vendas set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_vendas FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_atributos_qualidade_desempenho set_timestamp_on_ind_atributos_qualidade_desempenho; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_atributos_qualidade_desempenho BEFORE UPDATE ON public.ind_atributos_qualidade_desempenho FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_categorias_desagregacao set_timestamp_on_ind_categorias_desagregacao; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_categorias_desagregacao BEFORE UPDATE ON public.ind_categorias_desagregacao FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_definicoes_indicadores set_timestamp_on_ind_definicoes_indicadores; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_definicoes_indicadores BEFORE UPDATE ON public.ind_definicoes_indicadores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_dimensoes_indicadores set_timestamp_on_ind_dimensoes_indicadores; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_dimensoes_indicadores BEFORE UPDATE ON public.ind_dimensoes_indicadores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_fontes_dados set_timestamp_on_ind_fontes_dados; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_fontes_dados BEFORE UPDATE ON public.ind_fontes_dados FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_metas_indicadores set_timestamp_on_ind_metas_indicadores; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_metas_indicadores BEFORE UPDATE ON public.ind_metas_indicadores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_niveis_abrangencia set_timestamp_on_ind_niveis_abrangencia; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_niveis_abrangencia BEFORE UPDATE ON public.ind_niveis_abrangencia FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_opcoes_desagregacao set_timestamp_on_ind_opcoes_desagregacao; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_opcoes_desagregacao BEFORE UPDATE ON public.ind_opcoes_desagregacao FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_periodicidades set_timestamp_on_ind_periodicidades; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_periodicidades BEFORE UPDATE ON public.ind_periodicidades FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_relacoes_indicadores set_timestamp_on_ind_relacoes_indicadores; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_relacoes_indicadores BEFORE UPDATE ON public.ind_relacoes_indicadores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_unidades_medida set_timestamp_on_ind_unidades_medida; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_unidades_medida BEFORE UPDATE ON public.ind_unidades_medida FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


--
-- Name: ind_valores_indicadores set_timestamp_on_ind_valores_indicadores; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp_on_ind_valores_indicadores BEFORE UPDATE ON public.ind_valores_indicadores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();


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
-- Name: auth_assignment auth_assignment_item_name_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_assignment
    ADD CONSTRAINT auth_assignment_item_name_fkey FOREIGN KEY (item_name) REFERENCES public.auth_item(name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_item_child auth_item_child_child_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_item_child
    ADD CONSTRAINT auth_item_child_child_fkey FOREIGN KEY (child) REFERENCES public.auth_item(name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_item_child auth_item_child_parent_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_item_child
    ADD CONSTRAINT auth_item_child_parent_fkey FOREIGN KEY (parent) REFERENCES public.auth_item(name) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: auth_item auth_item_rule_name_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.auth_item
    ADD CONSTRAINT auth_item_rule_name_fkey FOREIGN KEY (rule_name) REFERENCES public.auth_rule(name) ON UPDATE CASCADE ON DELETE SET NULL;


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
-- Name: social_account fk_user_account; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.social_account
    ADD CONSTRAINT fk_user_account FOREIGN KEY (user_id) REFERENCES public."user"(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: profile fk_user_profile; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profile
    ADD CONSTRAINT fk_user_profile FOREIGN KEY (user_id) REFERENCES public."user"(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: token fk_user_token; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.token
    ADD CONSTRAINT fk_user_token FOREIGN KEY (user_id) REFERENCES public."user"(id) ON UPDATE RESTRICT ON DELETE CASCADE;


--
-- Name: ind_atributos_qualidade_desempenho ind_atributos_qualidade_desempenho_id_indicador_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_atributos_qualidade_desempenho
    ADD CONSTRAINT ind_atributos_qualidade_desempenho_id_indicador_fkey FOREIGN KEY (id_indicador) REFERENCES public.ind_definicoes_indicadores(id_indicador) ON DELETE CASCADE;


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_id_dimensao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_id_dimensao_fkey FOREIGN KEY (id_dimensao) REFERENCES public.ind_dimensoes_indicadores(id_dimensao) ON DELETE SET NULL;


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_id_fonte_padrao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_id_fonte_padrao_fkey FOREIGN KEY (id_fonte_padrao) REFERENCES public.ind_fontes_dados(id_fonte);


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_id_periodicidade_ideal_divulgac_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_id_periodicidade_ideal_divulgac_fkey FOREIGN KEY (id_periodicidade_ideal_divulgacao) REFERENCES public.ind_periodicidades(id_periodicidade);


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_id_periodicidade_ideal_medicao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_id_periodicidade_ideal_medicao_fkey FOREIGN KEY (id_periodicidade_ideal_medicao) REFERENCES public.ind_periodicidades(id_periodicidade);


--
-- Name: ind_definicoes_indicadores ind_definicoes_indicadores_id_unidade_medida_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_definicoes_indicadores
    ADD CONSTRAINT ind_definicoes_indicadores_id_unidade_medida_fkey FOREIGN KEY (id_unidade_medida) REFERENCES public.ind_unidades_medida(id_unidade);


--
-- Name: many_sys_modulos_has_many_ind_dimensoes_indicadores ind_dimensoes_indicadores_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_ind_dimensoes_indicadores
    ADD CONSTRAINT ind_dimensoes_indicadores_fk FOREIGN KEY (id_dimensao_ind_dimensoes_indicadores) REFERENCES public.ind_dimensoes_indicadores(id_dimensao) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: ind_dimensoes_indicadores ind_dimensoes_indicadores_id_dimensao_pai_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_dimensoes_indicadores
    ADD CONSTRAINT ind_dimensoes_indicadores_id_dimensao_pai_fkey FOREIGN KEY (id_dimensao_pai) REFERENCES public.ind_dimensoes_indicadores(id_dimensao) ON DELETE SET NULL;


--
-- Name: ind_metas_indicadores ind_metas_indicadores_id_indicador_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_metas_indicadores
    ADD CONSTRAINT ind_metas_indicadores_id_indicador_fkey FOREIGN KEY (id_indicador) REFERENCES public.ind_definicoes_indicadores(id_indicador) ON DELETE CASCADE;


--
-- Name: ind_metas_indicadores ind_metas_indicadores_id_nivel_abrangencia_aplicavel_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_metas_indicadores
    ADD CONSTRAINT ind_metas_indicadores_id_nivel_abrangencia_aplicavel_fkey FOREIGN KEY (id_nivel_abrangencia_aplicavel) REFERENCES public.ind_niveis_abrangencia(id_nivel_abrangencia);


--
-- Name: ind_niveis_abrangencia ind_niveis_abrangencia_id_nivel_pai_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_niveis_abrangencia
    ADD CONSTRAINT ind_niveis_abrangencia_id_nivel_pai_fkey FOREIGN KEY (id_nivel_pai) REFERENCES public.ind_niveis_abrangencia(id_nivel_abrangencia) ON DELETE SET NULL;


--
-- Name: ind_opcoes_desagregacao ind_opcoes_desagregacao_id_categoria_desagregacao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_opcoes_desagregacao
    ADD CONSTRAINT ind_opcoes_desagregacao_id_categoria_desagregacao_fkey FOREIGN KEY (id_categoria_desagregacao) REFERENCES public.ind_categorias_desagregacao(id_categoria_desagregacao) ON DELETE CASCADE;


--
-- Name: ind_relacoes_indicadores ind_relacoes_indicadores_id_indicador_destino_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_relacoes_indicadores
    ADD CONSTRAINT ind_relacoes_indicadores_id_indicador_destino_fkey FOREIGN KEY (id_indicador_destino) REFERENCES public.ind_definicoes_indicadores(id_indicador) ON DELETE CASCADE;


--
-- Name: ind_relacoes_indicadores ind_relacoes_indicadores_id_indicador_origem_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_relacoes_indicadores
    ADD CONSTRAINT ind_relacoes_indicadores_id_indicador_origem_fkey FOREIGN KEY (id_indicador_origem) REFERENCES public.ind_definicoes_indicadores(id_indicador) ON DELETE CASCADE;


--
-- Name: ind_valores_indicadores_desagregacoes ind_valores_indicadores_desagregacoe_id_opcao_desagregacao_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores_desagregacoes
    ADD CONSTRAINT ind_valores_indicadores_desagregacoe_id_opcao_desagregacao_fkey FOREIGN KEY (id_opcao_desagregacao) REFERENCES public.ind_opcoes_desagregacao(id_opcao_desagregacao) ON DELETE CASCADE;


--
-- Name: ind_valores_indicadores_desagregacoes ind_valores_indicadores_desagregacoes_id_valor_indicador_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores_desagregacoes
    ADD CONSTRAINT ind_valores_indicadores_desagregacoes_id_valor_indicador_fkey FOREIGN KEY (id_valor_indicador) REFERENCES public.ind_valores_indicadores(id_valor) ON DELETE CASCADE;


--
-- Name: ind_valores_indicadores ind_valores_indicadores_id_fonte_dado_especifica_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores
    ADD CONSTRAINT ind_valores_indicadores_id_fonte_dado_especifica_fkey FOREIGN KEY (id_fonte_dado_especifica) REFERENCES public.ind_fontes_dados(id_fonte);


--
-- Name: ind_valores_indicadores ind_valores_indicadores_id_indicador_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores
    ADD CONSTRAINT ind_valores_indicadores_id_indicador_fkey FOREIGN KEY (id_indicador) REFERENCES public.ind_definicoes_indicadores(id_indicador) ON DELETE CASCADE;


--
-- Name: ind_valores_indicadores ind_valores_indicadores_id_nivel_abrangencia_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.ind_valores_indicadores
    ADD CONSTRAINT ind_valores_indicadores_id_nivel_abrangencia_fkey FOREIGN KEY (id_nivel_abrangencia) REFERENCES public.ind_niveis_abrangencia(id_nivel_abrangencia);


--
-- Name: prest_clientes prest_clientes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_parcelas prest_parcelas_status_parcela_codigo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_status_parcela_codigo_fkey FOREIGN KEY (status_parcela_codigo) REFERENCES public.prest_status_parcela(codigo) ON DELETE RESTRICT;


--
-- Name: prest_parcelas prest_parcelas_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_parcelas prest_parcelas_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON DELETE CASCADE;


--
-- Name: prest_produtos prest_produtos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produtos
    ADD CONSTRAINT prest_produtos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_venda_itens prest_venda_itens_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_venda_itens
    ADD CONSTRAINT prest_venda_itens_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE RESTRICT;


--
-- Name: prest_venda_itens prest_venda_itens_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_venda_itens
    ADD CONSTRAINT prest_venda_itens_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON DELETE CASCADE;


--
-- Name: prest_vendas prest_vendas_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendas
    ADD CONSTRAINT prest_vendas_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.prest_clientes(id) ON DELETE RESTRICT;


--
-- Name: prest_vendas prest_vendas_status_venda_codigo_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendas
    ADD CONSTRAINT prest_vendas_status_venda_codigo_fkey FOREIGN KEY (status_venda_codigo) REFERENCES public.prest_status_venda(codigo) ON DELETE RESTRICT;


--
-- Name: prest_vendas prest_vendas_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendas
    ADD CONSTRAINT prest_vendas_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: servico_catalogo_categorias servico_catalogo_categorias_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_categorias
    ADD CONSTRAINT servico_catalogo_categorias_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_catalogo_produto_categoria_assoc servico_catalogo_produto_categoria_assoc_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_produto_categoria_assoc
    ADD CONSTRAINT servico_catalogo_produto_categoria_assoc_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.servico_catalogo_categorias(id) ON DELETE CASCADE;


--
-- Name: servico_catalogo_produto_categoria_assoc servico_catalogo_produto_categoria_assoc_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_produto_categoria_assoc
    ADD CONSTRAINT servico_catalogo_produto_categoria_assoc_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.servico_produtos(id) ON DELETE CASCADE;


--
-- Name: servico_catalogo_produto_imagens servico_catalogo_produto_imagens_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_catalogo_produto_imagens
    ADD CONSTRAINT servico_catalogo_produto_imagens_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.servico_produtos(id) ON DELETE CASCADE;


--
-- Name: servico_clientes servico_clientes_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_clientes
    ADD CONSTRAINT servico_clientes_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_clientes servico_clientes_tipo_pessoa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_clientes
    ADD CONSTRAINT servico_clientes_tipo_pessoa_id_fkey FOREIGN KEY (tipo_pessoa_id) REFERENCES public.servico_tipos_pessoa(id) ON DELETE RESTRICT;


--
-- Name: servico_contas_pagar servico_contas_pagar_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar
    ADD CONSTRAINT servico_contas_pagar_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_contas_pagar servico_contas_pagar_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar
    ADD CONSTRAINT servico_contas_pagar_lote_id_fkey FOREIGN KEY (lote_id) REFERENCES public.servico_lotes(id) ON DELETE SET NULL;


--
-- Name: servico_contas_pagar servico_contas_pagar_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar
    ADD CONSTRAINT servico_contas_pagar_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.servico_status_conta_financeira(id) ON DELETE RESTRICT;


--
-- Name: servico_contas_pagar servico_contas_pagar_terceiro_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_pagar
    ADD CONSTRAINT servico_contas_pagar_terceiro_id_fkey FOREIGN KEY (terceiro_id) REFERENCES public.servico_terceiros(id) ON DELETE RESTRICT;


--
-- Name: servico_contas_receber servico_contas_receber_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber
    ADD CONSTRAINT servico_contas_receber_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.servico_clientes(id) ON DELETE RESTRICT;


--
-- Name: servico_contas_receber servico_contas_receber_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber
    ADD CONSTRAINT servico_contas_receber_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_contas_receber servico_contas_receber_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber
    ADD CONSTRAINT servico_contas_receber_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.servico_pedidos_venda(id) ON DELETE SET NULL;


--
-- Name: servico_contas_receber servico_contas_receber_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_contas_receber
    ADD CONSTRAINT servico_contas_receber_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.servico_status_conta_financeira(id) ON DELETE RESTRICT;


--
-- Name: servico_etapas_producao servico_etapas_producao_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_etapas_producao
    ADD CONSTRAINT servico_etapas_producao_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_ficha_tecnica servico_ficha_tecnica_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ficha_tecnica
    ADD CONSTRAINT servico_ficha_tecnica_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_ficha_tecnica servico_ficha_tecnica_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ficha_tecnica
    ADD CONSTRAINT servico_ficha_tecnica_material_id_fkey FOREIGN KEY (material_id) REFERENCES public.servico_materiais(id) ON DELETE RESTRICT;


--
-- Name: servico_ficha_tecnica servico_ficha_tecnica_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ficha_tecnica
    ADD CONSTRAINT servico_ficha_tecnica_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.servico_produtos(id) ON DELETE CASCADE;


--
-- Name: servico_lotes servico_lotes_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes
    ADD CONSTRAINT servico_lotes_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_lotes servico_lotes_etapa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes
    ADD CONSTRAINT servico_lotes_etapa_id_fkey FOREIGN KEY (etapa_id) REFERENCES public.servico_etapas_producao(id) ON DELETE RESTRICT;


--
-- Name: servico_lotes servico_lotes_ordem_producao_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes
    ADD CONSTRAINT servico_lotes_ordem_producao_id_fkey FOREIGN KEY (ordem_producao_id) REFERENCES public.servico_ordens_producao(id) ON DELETE CASCADE;


--
-- Name: servico_lotes servico_lotes_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes
    ADD CONSTRAINT servico_lotes_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.servico_status_lote(id) ON DELETE RESTRICT;


--
-- Name: servico_lotes servico_lotes_terceiro_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_lotes
    ADD CONSTRAINT servico_lotes_terceiro_id_fkey FOREIGN KEY (terceiro_id) REFERENCES public.servico_terceiros(id) ON DELETE RESTRICT;


--
-- Name: servico_materiais servico_materiais_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_materiais
    ADD CONSTRAINT servico_materiais_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_movimentacoes_estoque servico_movimentacoes_estoque_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_movimentacoes_estoque
    ADD CONSTRAINT servico_movimentacoes_estoque_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_movimentacoes_estoque servico_movimentacoes_estoque_material_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_movimentacoes_estoque
    ADD CONSTRAINT servico_movimentacoes_estoque_material_id_fkey FOREIGN KEY (material_id) REFERENCES public.servico_materiais(id) ON DELETE RESTRICT;


--
-- Name: servico_movimentacoes_estoque servico_movimentacoes_estoque_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_movimentacoes_estoque
    ADD CONSTRAINT servico_movimentacoes_estoque_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.servico_produtos(id) ON DELETE RESTRICT;


--
-- Name: servico_movimentacoes_estoque servico_movimentacoes_estoque_tipo_movimento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_movimentacoes_estoque
    ADD CONSTRAINT servico_movimentacoes_estoque_tipo_movimento_id_fkey FOREIGN KEY (tipo_movimento_id) REFERENCES public.servico_tipos_movimento_estoque(id) ON DELETE RESTRICT;


--
-- Name: servico_ordens_producao servico_ordens_producao_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ordens_producao
    ADD CONSTRAINT servico_ordens_producao_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_ordens_producao servico_ordens_producao_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ordens_producao
    ADD CONSTRAINT servico_ordens_producao_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.servico_produtos(id) ON DELETE RESTRICT;


--
-- Name: servico_ordens_producao servico_ordens_producao_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_ordens_producao
    ADD CONSTRAINT servico_ordens_producao_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.servico_status_ordem_producao(id) ON DELETE RESTRICT;


--
-- Name: servico_pedido_venda_itens servico_pedido_venda_itens_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedido_venda_itens
    ADD CONSTRAINT servico_pedido_venda_itens_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_pedido_venda_itens servico_pedido_venda_itens_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedido_venda_itens
    ADD CONSTRAINT servico_pedido_venda_itens_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.servico_pedidos_venda(id) ON DELETE CASCADE;


--
-- Name: servico_pedido_venda_itens servico_pedido_venda_itens_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedido_venda_itens
    ADD CONSTRAINT servico_pedido_venda_itens_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.servico_produtos(id) ON DELETE RESTRICT;


--
-- Name: servico_pedidos_venda servico_pedidos_venda_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedidos_venda
    ADD CONSTRAINT servico_pedidos_venda_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.servico_clientes(id) ON DELETE RESTRICT;


--
-- Name: servico_pedidos_venda servico_pedidos_venda_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedidos_venda
    ADD CONSTRAINT servico_pedidos_venda_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_pedidos_venda servico_pedidos_venda_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_pedidos_venda
    ADD CONSTRAINT servico_pedidos_venda_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.servico_status_pedido_venda(id) ON DELETE RESTRICT;


--
-- Name: servico_produtos servico_produtos_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_produtos
    ADD CONSTRAINT servico_produtos_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_qualidade_defeitos servico_qualidade_defeitos_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_qualidade_defeitos
    ADD CONSTRAINT servico_qualidade_defeitos_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_qualidade_defeitos servico_qualidade_defeitos_lote_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_qualidade_defeitos
    ADD CONSTRAINT servico_qualidade_defeitos_lote_id_fkey FOREIGN KEY (lote_id) REFERENCES public.servico_lotes(id) ON DELETE CASCADE;


--
-- Name: servico_terceiros servico_terceiros_empresa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_terceiros
    ADD CONSTRAINT servico_terceiros_empresa_id_fkey FOREIGN KEY (empresa_id) REFERENCES public.servico_empresas(id) ON DELETE CASCADE;


--
-- Name: servico_terceiros servico_terceiros_tipo_pessoa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.servico_terceiros
    ADD CONSTRAINT servico_terceiros_tipo_pessoa_id_fkey FOREIGN KEY (tipo_pessoa_id) REFERENCES public.servico_tipos_pessoa(id) ON DELETE RESTRICT;


--
-- Name: many_sys_modulos_has_many_user sys_modulos_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_user
    ADD CONSTRAINT sys_modulos_fk FOREIGN KEY (id_sys_modulos) REFERENCES public.sys_modulos(id) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: many_sys_modulos_has_many_ind_dimensoes_indicadores sys_modulos_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_ind_dimensoes_indicadores
    ADD CONSTRAINT sys_modulos_fk FOREIGN KEY (id_sys_modulos) REFERENCES public.sys_modulos(id) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: many_sys_modulos_has_many_user user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_user
    ADD CONSTRAINT user_fk FOREIGN KEY (id_user) REFERENCES public."user"(id) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

