--
-- PostgreSQL database dump
--

\restrict eC7xSkPEEIHWWMMORtjVmNoMJHUWLg6PyHlttZmM57w6R1ky7a9VKq5NfyhG73w

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

--
-- Name: unaccent; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS unaccent WITH SCHEMA public;


--
-- Name: EXTENSION unaccent; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION unaccent IS 'text search dictionary that removes accents';


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
-- Name: atualizar_data_atualizacao_regras_parcelamento(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.atualizar_data_atualizacao_regras_parcelamento() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.atualizar_data_atualizacao_regras_parcelamento() OWNER TO postgres;

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

--
-- Name: update_prest_comissao_config_timestamp(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_prest_comissao_config_timestamp() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.data_atualizacao = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_prest_comissao_config_timestamp() OWNER TO postgres;

--
-- Name: verificar_acesso_modulo(uuid, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.verificar_acesso_modulo(p_usuario_id uuid, p_modulo_codigo character varying) RETURNS boolean
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_tem_acesso BOOLEAN := FALSE;
BEGIN
    -- Verifica acesso direto
    SELECT EXISTS(
        SELECT 1 
        FROM sis_usuario_modulos um
        JOIN sis_modulos m ON m.id = um.modulo_id
        WHERE um.usuario_id = p_usuario_id
        AND m.codigo = p_modulo_codigo
        AND um.ativo = true
        AND (um.data_fim IS NULL OR um.data_fim >= CURRENT_DATE)
    ) INTO v_tem_acesso;
    
    IF v_tem_acesso THEN
        RETURN TRUE;
    END IF;
    
    -- Verifica assinatura ativa
    SELECT EXISTS(
        SELECT 1
        FROM sis_assinaturas a
        JOIN sis_plano_modulos pm ON pm.plano_id = a.plano_id
        JOIN sis_modulos m ON m.id = pm.modulo_id
        WHERE a.usuario_id = p_usuario_id
        AND m.codigo = p_modulo_codigo
        AND a.status IN ('ativa', 'trial')
        AND (a.data_fim IS NULL OR a.data_fim >= CURRENT_DATE)
    ) INTO v_tem_acesso;
    
    RETURN v_tem_acesso;
END;
$$;


ALTER FUNCTION public.verificar_acesso_modulo(p_usuario_id uuid, p_modulo_codigo character varying) OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: social_account; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.social_account (
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
-- Name: asaas_clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.asaas_clientes (
    id integer NOT NULL,
    usuario_id uuid NOT NULL,
    customer_asaas_id character varying(100) NOT NULL,
    cpf_cnpj character varying(20) NOT NULL,
    nome character varying(255),
    email character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.asaas_clientes OWNER TO postgres;

--
-- Name: TABLE asaas_clientes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.asaas_clientes IS 'Cache de clientes Asaas';


--
-- Name: asaas_clientes_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asaas_clientes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asaas_clientes_id_seq OWNER TO postgres;

--
-- Name: asaas_clientes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asaas_clientes_id_seq OWNED BY public.asaas_clientes.id;


--
-- Name: asaas_cobrancas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.asaas_cobrancas (
    id integer NOT NULL,
    payment_id character varying(100) NOT NULL,
    external_reference character varying(100) NOT NULL,
    usuario_id uuid NOT NULL,
    cliente_id uuid,
    customer_asaas_id character varying(100),
    valor numeric(10,2) NOT NULL,
    valor_recebido numeric(10,2),
    metodo_pagamento character varying(50) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying,
    status_asaas character varying(50),
    vencimento date,
    data_pagamento timestamp without time zone,
    dados_request jsonb,
    dados_cobranca jsonb,
    ambiente character varying(20) DEFAULT 'producao'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    ultima_atualizacao timestamp without time zone,
    pedido_id uuid,
    colaborador_id character varying(255)
);


ALTER TABLE public.asaas_cobrancas OWNER TO postgres;

--
-- Name: TABLE asaas_cobrancas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.asaas_cobrancas IS 'Cobranças da Asaas';


--
-- Name: asaas_cobrancas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asaas_cobrancas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asaas_cobrancas_id_seq OWNER TO postgres;

--
-- Name: asaas_cobrancas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asaas_cobrancas_id_seq OWNED BY public.asaas_cobrancas.id;


--
-- Name: auth_assignment; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.auth_assignment (
    item_name character varying(64) NOT NULL,
    user_id character varying(64) NOT NULL,
    created_at integer
);


ALTER TABLE public.auth_assignment OWNER TO postgres;

--
-- Name: auth_item; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.auth_item (
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

CREATE TABLE IF NOT EXISTS public.auth_item_child (
    parent character varying(64) NOT NULL,
    child character varying(64) NOT NULL
);


ALTER TABLE public.auth_item_child OWNER TO postgres;

--
-- Name: auth_rule; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.auth_rule (
    name character varying(64) NOT NULL,
    data bytea,
    created_at integer,
    updated_at integer
);


ALTER TABLE public.auth_rule OWNER TO postgres;

--
-- Name: delivery_admin_contas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.delivery_admin_contas (
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

CREATE TABLE IF NOT EXISTS public.delivery_categorias (
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

CREATE TABLE IF NOT EXISTS public.delivery_clientes (
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

CREATE TABLE IF NOT EXISTS public.delivery_complementos (
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

CREATE TABLE IF NOT EXISTS public.delivery_configuracoes_estabelecimento (
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

CREATE TABLE IF NOT EXISTS public.delivery_enderecos_cliente (
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

CREATE TABLE IF NOT EXISTS public.delivery_entregadores (
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

CREATE TABLE IF NOT EXISTS public.delivery_entregas (
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

CREATE TABLE IF NOT EXISTS public.delivery_estabelecimentos (
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

CREATE TABLE IF NOT EXISTS public.delivery_movimentacoes_financeiras (
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

CREATE TABLE IF NOT EXISTS public.delivery_pedido_complementos (
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

CREATE TABLE IF NOT EXISTS public.delivery_pedido_itens (
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

CREATE TABLE IF NOT EXISTS public.delivery_pedidos (
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

CREATE TABLE IF NOT EXISTS public.delivery_produto_complementos (
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

CREATE TABLE IF NOT EXISTS public.delivery_produtos (
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

CREATE TABLE IF NOT EXISTS public.delivery_promocoes (
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

CREATE TABLE IF NOT EXISTS public.delivery_status_financeiro (
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

CREATE TABLE IF NOT EXISTS public.delivery_status_pedido (
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

CREATE TABLE IF NOT EXISTS public.delivery_tipos_entrega (
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

CREATE TABLE IF NOT EXISTS public.delivery_tipos_pagamento (
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

CREATE TABLE IF NOT EXISTS public.delivery_tipos_pessoa (
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

CREATE TABLE IF NOT EXISTS public.delivery_uso_promocoes (
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

CREATE TABLE IF NOT EXISTS public.delivery_usuarios_estabelecimento (
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

CREATE OR REPLACE VIEW public.delivery_v_produtos_mais_vendidos AS
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

CREATE OR REPLACE VIEW public.delivery_v_vendas_diarias AS
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

CREATE TABLE IF NOT EXISTS public.delivery_variacoes_produto (
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

CREATE TABLE IF NOT EXISTS public.ind_atributos_qualidade_desempenho (
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

CREATE TABLE IF NOT EXISTS public.ind_categorias_desagregacao (
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

CREATE TABLE IF NOT EXISTS public.ind_definicoes_indicadores (
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
    CONSTRAINT ind_definicoes_indicadores_polaridade_check CHECK (((polaridade)::text = ANY (ARRAY[('QUANTO_MAIOR_MELHOR'::character varying)::text, ('QUANTO_MENOR_MELHOR'::character varying)::text, ('DENTRO_DA_FAIXA_MELHOR'::character varying)::text, ('NEUTRO'::character varying)::text])))
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

CREATE TABLE IF NOT EXISTS public.ind_dimensoes_indicadores (
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

CREATE TABLE IF NOT EXISTS public.ind_fontes_dados (
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

CREATE TABLE IF NOT EXISTS public.ind_metas_indicadores (
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

CREATE TABLE IF NOT EXISTS public.ind_niveis_abrangencia (
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

CREATE TABLE IF NOT EXISTS public.ind_opcoes_desagregacao (
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

CREATE TABLE IF NOT EXISTS public.ind_periodicidades (
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

CREATE TABLE IF NOT EXISTS public.ind_relacoes_indicadores (
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

CREATE TABLE IF NOT EXISTS public.ind_unidades_medida (
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

CREATE TABLE IF NOT EXISTS public.ind_valores_indicadores (
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

CREATE TABLE IF NOT EXISTS public.ind_valores_indicadores_desagregacoes (
    id_valor_indicador integer CONSTRAINT ind_valores_indicadores_desagregaco_id_valor_indicador_not_null NOT NULL,
    id_opcao_desagregacao integer CONSTRAINT ind_valores_indicadores_desagreg_id_opcao_desagregacao_not_null NOT NULL
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

CREATE TABLE IF NOT EXISTS public.indica_producao_diaria (
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

CREATE TABLE IF NOT EXISTS public.indica_qualidade_defeitos (
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

CREATE TABLE IF NOT EXISTS public.indica_tempos_producao (
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
-- Name: loja_configuracao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.loja_configuracao (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    nome_loja character varying(255) NOT NULL,
    nome_fantasia character varying(255),
    razao_social character varying(255),
    cpf_cnpj character varying(18) NOT NULL,
    inscricao_estadual character varying(20),
    inscricao_municipal character varying(20),
    telefone character varying(20),
    celular character varying(20),
    email character varying(255),
    site character varying(255),
    cep character varying(10),
    logradouro character varying(255),
    numero character varying(20),
    complemento character varying(100),
    bairro character varying(100),
    cidade character varying(100),
    estado character varying(2),
    codigo_municipio_ibge character varying(7),
    logo_path character varying(500),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    pix_chave character varying(255),
    pix_cidade character varying(255),
    pix_nome character varying(255),
    aparencia_cor_primaria character varying(255),
    aparencia_cor_secundaria character varying(255),
    aparencia_tema character varying(255)
);


ALTER TABLE public.loja_configuracao OWNER TO postgres;

--
-- Name: many_sys_modulos_has_many_ind_dimensoes_indicadores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.many_sys_modulos_has_many_ind_dimensoes_indicadores (
    id_sys_modulos integer CONSTRAINT many_sys_modulos_has_many_ind_dimensoes_id_sys_modulos_not_null NOT NULL,
    id_dimensao_ind_dimensoes_indicadores integer CONSTRAINT many_sys_modulos_has_many_i_id_dimensao_ind_dimensoes__not_null NOT NULL
);


ALTER TABLE public.many_sys_modulos_has_many_ind_dimensoes_indicadores OWNER TO postgres;

--
-- Name: many_sys_modulos_has_many_user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.many_sys_modulos_has_many_user (
    id_sys_modulos integer NOT NULL,
    id_user integer NOT NULL
);


ALTER TABLE public.many_sys_modulos_has_many_user OWNER TO postgres;

--
-- Name: mercadopago_preferencias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.mercadopago_preferencias (
    id integer NOT NULL,
    preference_id character varying(100) NOT NULL,
    external_reference character varying(100) NOT NULL,
    usuario_id uuid NOT NULL,
    payment_id character varying(100),
    payment_status character varying(50) DEFAULT 'pending'::character varying,
    transaction_amount numeric(10,2),
    payment_type character varying(50),
    valor_total numeric(10,2) NOT NULL,
    status character varying(50) DEFAULT 'pending'::character varying,
    dados_request jsonb,
    ultima_atualizacao timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.mercadopago_preferencias OWNER TO postgres;

--
-- Name: TABLE mercadopago_preferencias; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.mercadopago_preferencias IS 'Rastreamento de transações do Mercado Pago';


--
-- Name: COLUMN mercadopago_preferencias.preference_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.mercadopago_preferencias.preference_id IS 'ID da preferência gerada pelo Mercado Pago';


--
-- Name: COLUMN mercadopago_preferencias.external_reference; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.mercadopago_preferencias.external_reference IS 'Referência única do pedido no sistema';


--
-- Name: COLUMN mercadopago_preferencias.dados_request; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.mercadopago_preferencias.dados_request IS 'JSON com dados originais da requisição';


--
-- Name: mercadopago_preferencias_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.mercadopago_preferencias_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.mercadopago_preferencias_id_seq OWNER TO postgres;

--
-- Name: mercadopago_preferencias_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.mercadopago_preferencias_id_seq OWNED BY public.mercadopago_preferencias.id;


--
-- Name: migration; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.migration (
    version character varying(180) NOT NULL,
    apply_time integer
);


ALTER TABLE public.migration OWNER TO postgres;

--
-- Name: orcamento_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.orcamento_itens (
    id integer NOT NULL,
    orcamento_id integer NOT NULL,
    produto_id uuid NOT NULL,
    quantidade numeric(10,3) DEFAULT 1 NOT NULL,
    preco_unitario numeric(10,2) NOT NULL,
    desconto_valor numeric(10,2) DEFAULT 0.00,
    subtotal numeric(10,2) NOT NULL,
    observacoes text
);


ALTER TABLE public.orcamento_itens OWNER TO postgres;

--
-- Name: orcamento_itens_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.orcamento_itens_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orcamento_itens_id_seq OWNER TO postgres;

--
-- Name: orcamento_itens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.orcamento_itens_id_seq OWNED BY public.orcamento_itens.id;


--
-- Name: orcamentos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.orcamentos (
    id integer NOT NULL,
    usuario_id uuid NOT NULL,
    cliente_id uuid,
    colaborador_vendedor_id uuid,
    valor_total numeric(10,2) DEFAULT 0.00 NOT NULL,
    desconto_valor numeric(10,2) DEFAULT 0.00,
    acrescimo_valor numeric(10,2) DEFAULT 0.00,
    acrescimo_tipo character varying(20) DEFAULT NULL::character varying,
    observacao_acrescimo text,
    observacoes text,
    status character varying(20) DEFAULT 'PENDENTE'::character varying,
    data_criacao timestamp without time zone DEFAULT now(),
    data_atualizacao timestamp without time zone DEFAULT now(),
    data_validade date,
    forma_pagamento_id uuid,
    hash character varying(255)
);


ALTER TABLE public.orcamentos OWNER TO postgres;

--
-- Name: orcamentos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.orcamentos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orcamentos_id_seq OWNER TO postgres;

--
-- Name: orcamentos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.orcamentos_id_seq OWNED BY public.orcamentos.id;


--
-- Name: prest_caixa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_caixa (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    colaborador_id uuid,
    data_abertura timestamp with time zone DEFAULT now() NOT NULL,
    data_fechamento timestamp with time zone,
    valor_inicial numeric(10,2) DEFAULT 0 NOT NULL,
    valor_final numeric(10,2),
    valor_esperado numeric(10,2),
    diferenca numeric(10,2),
    status character varying(20) DEFAULT 'ABERTO'::character varying NOT NULL,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT prest_caixa_status_check CHECK (((status)::text = ANY (ARRAY[('ABERTO'::character varying)::text, ('FECHADO'::character varying)::text, ('CANCELADO'::character varying)::text]))),
    CONSTRAINT prest_caixa_valor_inicial_check CHECK ((valor_inicial >= (0)::numeric))
);


ALTER TABLE public.prest_caixa OWNER TO postgres;

--
-- Name: TABLE prest_caixa; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_caixa IS 'Registra abertura e fechamento de caixa';


--
-- Name: COLUMN prest_caixa.valor_inicial; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa.valor_inicial IS 'Valor inicial do caixa na abertura';


--
-- Name: COLUMN prest_caixa.valor_final; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa.valor_final IS 'Valor final do caixa no fechamento';


--
-- Name: COLUMN prest_caixa.valor_esperado; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa.valor_esperado IS 'Valor esperado calculado (inicial + entradas - saídas)';


--
-- Name: COLUMN prest_caixa.diferenca; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa.diferenca IS 'Diferença entre valor final e valor esperado';


--
-- Name: prest_caixa_movimentacoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_caixa_movimentacoes (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    caixa_id uuid NOT NULL,
    tipo character varying(20) NOT NULL,
    categoria character varying(50),
    valor numeric(10,2) NOT NULL,
    descricao text NOT NULL,
    forma_pagamento_id uuid,
    venda_id uuid,
    parcela_id uuid,
    conta_pagar_id uuid,
    data_movimento timestamp with time zone DEFAULT now() NOT NULL,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT prest_caixa_movimentacoes_tipo_check CHECK (((tipo)::text = ANY (ARRAY[('ENTRADA'::character varying)::text, ('SAIDA'::character varying)::text]))),
    CONSTRAINT prest_caixa_movimentacoes_valor_check CHECK ((valor > (0)::numeric))
);


ALTER TABLE public.prest_caixa_movimentacoes OWNER TO postgres;

--
-- Name: TABLE prest_caixa_movimentacoes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_caixa_movimentacoes IS 'Registra todas as movimentações de entrada e saída do caixa';


--
-- Name: COLUMN prest_caixa_movimentacoes.tipo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa_movimentacoes.tipo IS 'ENTRADA ou SAIDA';


--
-- Name: COLUMN prest_caixa_movimentacoes.categoria; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa_movimentacoes.categoria IS 'Categoria da movimentação (ex: VENDA, PAGAMENTO, SUPRIMENTO, SANGRIA)';


--
-- Name: COLUMN prest_caixa_movimentacoes.venda_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa_movimentacoes.venda_id IS 'Referência à venda (se a movimentação for relacionada a uma venda)';


--
-- Name: COLUMN prest_caixa_movimentacoes.parcela_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa_movimentacoes.parcela_id IS 'Referência à parcela (se a movimentação for relacionada a um pagamento de parcela)';


--
-- Name: COLUMN prest_caixa_movimentacoes.conta_pagar_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_caixa_movimentacoes.conta_pagar_id IS 'Referência à conta a pagar (se a movimentação for relacionada a um pagamento)';


--
-- Name: prest_carteira_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_carteira_cobranca (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    periodo_id uuid NOT NULL,
    cobrador_id uuid NOT NULL,
    cliente_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    data_distribuicao timestamp with time zone DEFAULT now() NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    total_parcelas integer DEFAULT 0 NOT NULL,
    parcelas_pagas integer DEFAULT 0 NOT NULL,
    valor_total numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_recebido numeric(10,2) DEFAULT 0.00 NOT NULL,
    observacoes text,
    rota_id uuid
);


ALTER TABLE public.prest_carteira_cobranca OWNER TO postgres;

--
-- Name: prest_categorias; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_categorias (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    ativo boolean DEFAULT true NOT NULL,
    ordem integer DEFAULT 0 NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_categorias OWNER TO postgres;

--
-- Name: prest_clientes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_clientes (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
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
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    regiao_id uuid,
    senha_hash character varying(255),
    cnpj character varying(255),
    indicador_ie character varying(255),
    inscricao_estadual character varying(255),
    inscricao_municipal character varying(255),
    nome_responsavel character varying(255),
    razao_social character varying(255),
    tipo_pessoa character(1)
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
-- Name: COLUMN prest_clientes.senha_hash; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_clientes.senha_hash IS 'Hash da senha do cliente para autenticação na PWA';


--
-- Name: prest_cobranca_configuracao; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_cobranca_configuracao (
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

CREATE TABLE IF NOT EXISTS public.prest_cobranca_historico (
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

CREATE TABLE IF NOT EXISTS public.prest_cobranca_template (
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
-- Name: prest_colaboradores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_colaboradores (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    nome_completo character varying(150) NOT NULL,
    cpf character varying(11),
    telefone character varying(20),
    email character varying(100),
    eh_vendedor boolean DEFAULT false NOT NULL,
    eh_cobrador boolean DEFAULT false NOT NULL,
    percentual_comissao_venda numeric(5,2) DEFAULT 0.00 NOT NULL,
    percentual_comissao_cobranca numeric(5,2) DEFAULT 0.00 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    data_admissao date,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    eh_administrador boolean DEFAULT false NOT NULL,
    prest_usuario_login_id uuid,
    asaas_wallet_id character varying(255),
    CONSTRAINT check_pelo_menos_um_papel CHECK (((eh_vendedor = true) OR (eh_cobrador = true)))
);


ALTER TABLE public.prest_colaboradores OWNER TO postgres;

--
-- Name: COLUMN prest_colaboradores.eh_administrador; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_colaboradores.eh_administrador IS 'Flag que indica se o colaborador é administrador e tem acesso a todos os módulos';


--
-- Name: COLUMN prest_colaboradores.prest_usuario_login_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_colaboradores.prest_usuario_login_id IS 'FK para prest_usuarios.id - Registro de login do colaborador (eh_dono_loja = false). NULL se colaborador não tem login próprio.';


--
-- Name: prest_comissao_config; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_comissao_config (
    id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    colaborador_id uuid NOT NULL,
    tipo_comissao character varying(20) NOT NULL,
    categoria_id uuid,
    percentual numeric(5,2) DEFAULT 0 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    data_inicio date,
    data_fim date,
    observacoes text,
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    data_atualizacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_data_fim CHECK (((data_fim IS NULL) OR (data_inicio IS NULL) OR (data_fim >= data_inicio))),
    CONSTRAINT chk_percentual CHECK (((percentual >= (0)::numeric) AND (percentual <= (100)::numeric))),
    CONSTRAINT chk_tipo_comissao CHECK (((tipo_comissao)::text = ANY (ARRAY[('VENDA'::character varying)::text, ('COBRANCA'::character varying)::text])))
);


ALTER TABLE public.prest_comissao_config OWNER TO postgres;

--
-- Name: TABLE prest_comissao_config; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_comissao_config IS 'Configurações de comissões para colaboradores - permite múltiplas configurações por colaborador';


--
-- Name: COLUMN prest_comissao_config.id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.id IS 'ID único da configuração (UUID)';


--
-- Name: COLUMN prest_comissao_config.usuario_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.usuario_id IS 'Usuário proprietário da configuração';


--
-- Name: COLUMN prest_comissao_config.colaborador_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.colaborador_id IS 'Colaborador que receberá a comissão';


--
-- Name: COLUMN prest_comissao_config.tipo_comissao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.tipo_comissao IS 'Tipo de comissão: VENDA ou COBRANCA';


--
-- Name: COLUMN prest_comissao_config.categoria_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.categoria_id IS 'NULL = todas as categorias, ou ID específico de uma categoria';


--
-- Name: COLUMN prest_comissao_config.percentual; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.percentual IS 'Percentual de comissão (0-100)';


--
-- Name: COLUMN prest_comissao_config.ativo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.ativo IS 'Se a configuração está ativa';


--
-- Name: COLUMN prest_comissao_config.data_inicio; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.data_inicio IS 'Data de início da vigência (opcional)';


--
-- Name: COLUMN prest_comissao_config.data_fim; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.data_fim IS 'Data de fim da vigência (opcional)';


--
-- Name: COLUMN prest_comissao_config.observacoes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissao_config.observacoes IS 'Observações sobre a configuração';


--
-- Name: prest_comissoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_comissoes (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    colaborador_id uuid NOT NULL,
    venda_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    percentual_aplicado numeric(5,2) NOT NULL,
    valor_base numeric(10,2) NOT NULL,
    valor_comissao numeric(10,2) NOT NULL,
    status character varying(20) DEFAULT 'PENDENTE'::character varying NOT NULL,
    data_pagamento date,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    tipo_comissao character varying(20) DEFAULT 'VENDA'::character varying NOT NULL,
    parcela_id uuid,
    comissao_config_id uuid
);


ALTER TABLE public.prest_comissoes OWNER TO postgres;

--
-- Name: COLUMN prest_comissoes.comissao_config_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_comissoes.comissao_config_id IS 'Referência à configuração de comissão usada para calcular esta comissão';


--
-- Name: prest_compras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_compras (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    fornecedor_id uuid NOT NULL,
    numero_nota_fiscal character varying(50),
    serie_nota_fiscal character varying(10),
    data_compra date DEFAULT CURRENT_DATE NOT NULL,
    data_vencimento date,
    valor_total numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_frete numeric(10,2) DEFAULT 0.00,
    valor_desconto numeric(10,2) DEFAULT 0.00,
    forma_pagamento character varying(50),
    status_compra character varying(20) DEFAULT 'PENDENTE'::character varying NOT NULL,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    intervalo_parcelas integer,
    num_parcelas integer,
    com_nota boolean
);


ALTER TABLE public.prest_compras OWNER TO postgres;

--
-- Name: TABLE prest_compras; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_compras IS 'Cabeçalho de compras/resuprimentos realizados';


--
-- Name: COLUMN prest_compras.id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.id IS 'Identificador único da compra (UUID)';


--
-- Name: COLUMN prest_compras.usuario_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.usuario_id IS 'Referência ao prestanista (dono da loja)';


--
-- Name: COLUMN prest_compras.fornecedor_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.fornecedor_id IS 'Referência ao fornecedor';


--
-- Name: COLUMN prest_compras.numero_nota_fiscal; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.numero_nota_fiscal IS 'Número da nota fiscal';


--
-- Name: COLUMN prest_compras.data_compra; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.data_compra IS 'Data da compra';


--
-- Name: COLUMN prest_compras.valor_total; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.valor_total IS 'Valor total da compra (soma dos itens)';


--
-- Name: COLUMN prest_compras.status_compra; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_compras.status_compra IS 'Status da compra: PENDENTE, CONCLUIDA, CANCELADA';


--
-- Name: prest_configuracoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_configuracoes (
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

CREATE TABLE IF NOT EXISTS public.prest_contas_pagar (
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
-- Name: prest_cupons_fiscais; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_cupons_fiscais (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    venda_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    numero integer,
    serie integer,
    modelo character varying(2) DEFAULT '65'::character varying,
    chave_acesso character varying(44),
    xml_path text,
    pdf_path text,
    status character varying(20) DEFAULT 'PENDENTE'::character varying,
    ambiente integer DEFAULT 2,
    mensagem_retorno text,
    data_emissao timestamp with time zone,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_cupons_fiscais OWNER TO postgres;

--
-- Name: TABLE prest_cupons_fiscais; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_cupons_fiscais IS 'Registro de emissão de cupons fiscais (NFe/NFCe) vinculados a vendas.';


--
-- Name: prest_dados_financeiros; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_dados_financeiros (
    id integer NOT NULL,
    usuario_id uuid NOT NULL,
    produto_id uuid,
    taxa_fixa_percentual numeric(5,2) DEFAULT 0.00 NOT NULL,
    taxa_variavel_percentual numeric(5,2) DEFAULT 0.00 NOT NULL,
    lucro_liquido_percentual numeric(5,2) DEFAULT 0.00 NOT NULL,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_dados_financeiros OWNER TO postgres;

--
-- Name: TABLE prest_dados_financeiros; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_dados_financeiros IS 'Configurações financeiras para precificação inteligente (Markup Divisor). Pode ser global por loja ou específica por produto.';


--
-- Name: COLUMN prest_dados_financeiros.produto_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_dados_financeiros.produto_id IS 'NULL = configuração global da loja, preenchido = configuração específica do produto';


--
-- Name: prest_dados_financeiros_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.prest_dados_financeiros_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prest_dados_financeiros_id_seq OWNER TO postgres;

--
-- Name: prest_dados_financeiros_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.prest_dados_financeiros_id_seq OWNED BY public.prest_dados_financeiros.id;


--
-- Name: prest_disparo_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_disparo_itens (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    disparo_id uuid NOT NULL,
    produto_id character varying(36) NOT NULL,
    cliente_id character varying(36) DEFAULT NULL::character varying,
    canal character varying(30) NOT NULL,
    destino character varying(255) DEFAULT NULL::character varying,
    card_path character varying(500) DEFAULT NULL::character varying,
    card_url character varying(500) DEFAULT NULL::character varying,
    mensagem_personalizada text,
    status character varying(30) DEFAULT 'pendente'::character varying NOT NULL,
    erro_mensagem text,
    enviado_em timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_disparo_itens OWNER TO postgres;

--
-- Name: prest_disparos_massa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_disparos_massa (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id character varying(36) NOT NULL,
    titulo character varying(255) NOT NULL,
    canais jsonb DEFAULT '[]'::jsonb NOT NULL,
    configuracoes jsonb DEFAULT '{}'::jsonb,
    mensagem_texto text,
    status character varying(30) DEFAULT 'pendente'::character varying NOT NULL,
    total_itens integer DEFAULT 0 NOT NULL,
    itens_enviados integer DEFAULT 0 NOT NULL,
    itens_erro integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_disparos_massa OWNER TO postgres;

--
-- Name: prest_dispositivos_pagamento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_dispositivos_pagamento (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(100) NOT NULL,
    device_id character varying(100) NOT NULL,
    tipo character varying(50) DEFAULT 'mercadopago_point'::character varying,
    status character varying(20) DEFAULT 'ativo'::character varying,
    data_criacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp with time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_dispositivos_pagamento OWNER TO postgres;

--
-- Name: prest_estoque_movimentacoes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_estoque_movimentacoes (
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
-- Name: prest_financeiro_mensal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_financeiro_mensal (
    id integer NOT NULL,
    usuario_id character varying(255) NOT NULL,
    mes_referencia date NOT NULL,
    faturamento_total numeric(15,2) DEFAULT 0,
    despesas_fixas_total numeric(15,2) DEFAULT 0,
    despesas_variaveis_total numeric(15,2) DEFAULT 0,
    custo_mercadoria_vendida numeric(15,2) DEFAULT 0,
    data_criacao timestamp without time zone DEFAULT now(),
    data_atualizacao timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_financeiro_mensal OWNER TO postgres;

--
-- Name: prest_financeiro_mensal_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.prest_financeiro_mensal_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prest_financeiro_mensal_id_seq OWNER TO postgres;

--
-- Name: prest_financeiro_mensal_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.prest_financeiro_mensal_id_seq OWNED BY public.prest_financeiro_mensal.id;


--
-- Name: prest_formas_pagamento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_formas_pagamento (
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
-- Name: prest_fornecedores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_fornecedores (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    nome_fantasia character varying(150) NOT NULL,
    razao_social character varying(255),
    cnpj character varying(18),
    cpf character varying(14),
    inscricao_estadual character varying(50),
    telefone character varying(20),
    email character varying(100),
    endereco character varying(255),
    numero character varying(20),
    complemento character varying(100),
    bairro character varying(100),
    cidade character varying(100),
    estado character varying(2),
    cep character varying(9),
    observacoes text,
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_fornecedores OWNER TO postgres;

--
-- Name: TABLE prest_fornecedores; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_fornecedores IS 'Cadastro de fornecedores de cada prestanista';


--
-- Name: COLUMN prest_fornecedores.id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.id IS 'Identificador único do fornecedor (UUID)';


--
-- Name: COLUMN prest_fornecedores.usuario_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.usuario_id IS 'Referência ao prestanista (dono da loja)';


--
-- Name: COLUMN prest_fornecedores.nome_fantasia; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.nome_fantasia IS 'Nome fantasia do fornecedor';


--
-- Name: COLUMN prest_fornecedores.razao_social; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.razao_social IS 'Razão social do fornecedor (se pessoa jurídica)';


--
-- Name: COLUMN prest_fornecedores.cnpj; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.cnpj IS 'CNPJ do fornecedor (se pessoa jurídica)';


--
-- Name: COLUMN prest_fornecedores.cpf; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.cpf IS 'CPF do fornecedor (se pessoa física)';


--
-- Name: COLUMN prest_fornecedores.ativo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_fornecedores.ativo IS 'Indica se o fornecedor está ativo';


--
-- Name: prest_historico_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_historico_cobranca (
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
-- Name: prest_itens_compra; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_itens_compra (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    compra_id uuid NOT NULL,
    produto_id uuid NOT NULL,
    quantidade numeric(10,3) NOT NULL,
    preco_unitario numeric(10,2) NOT NULL,
    valor_total_item numeric(10,2) NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT prest_itens_compra_preco_check CHECK ((preco_unitario >= (0)::numeric)),
    CONSTRAINT prest_itens_compra_quantidade_check CHECK ((quantidade > (0)::numeric)),
    CONSTRAINT prest_itens_compra_valor_check CHECK ((valor_total_item >= (0)::numeric))
);


ALTER TABLE public.prest_itens_compra OWNER TO postgres;

--
-- Name: TABLE prest_itens_compra; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_itens_compra IS 'Itens de cada compra (produtos comprados)';


--
-- Name: COLUMN prest_itens_compra.id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_itens_compra.id IS 'Identificador único do item (UUID)';


--
-- Name: COLUMN prest_itens_compra.compra_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_itens_compra.compra_id IS 'Referência à compra';


--
-- Name: COLUMN prest_itens_compra.produto_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_itens_compra.produto_id IS 'Referência ao produto comprado';


--
-- Name: COLUMN prest_itens_compra.quantidade; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_itens_compra.quantidade IS 'Quantidade comprada do produto';


--
-- Name: COLUMN prest_itens_compra.preco_unitario; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_itens_compra.preco_unitario IS 'Preço unitário pago pelo produto nesta compra';


--
-- Name: COLUMN prest_itens_compra.valor_total_item; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_itens_compra.valor_total_item IS 'Valor total do item (quantidade * preco_unitario)';


--
-- Name: prest_marketplace_config; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_marketplace_config (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    marketplace character varying(50) NOT NULL,
    ativo boolean DEFAULT false,
    client_id character varying(255),
    client_secret character varying(255),
    access_token text,
    refresh_token text,
    token_expira_em timestamp without time zone,
    sincronizar_produtos boolean DEFAULT true,
    sincronizar_estoque boolean DEFAULT true,
    sincronizar_pedidos boolean DEFAULT true,
    intervalo_sync_minutos integer DEFAULT 15,
    ultima_sync timestamp without time zone,
    data_criacao timestamp without time zone DEFAULT now(),
    data_atualizacao timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_marketplace_config OWNER TO postgres;

--
-- Name: TABLE prest_marketplace_config; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_marketplace_config IS 'Configurações e credenciais de integração com marketplaces';


--
-- Name: COLUMN prest_marketplace_config.marketplace; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_config.marketplace IS 'Nome do marketplace: MERCADO_LIVRE, SHOPEE, MAGAZINE_LUIZA, AMAZON';


--
-- Name: COLUMN prest_marketplace_config.ativo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_config.ativo IS 'Se a integração está ativa para este marketplace';


--
-- Name: COLUMN prest_marketplace_config.intervalo_sync_minutos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_config.intervalo_sync_minutos IS 'Intervalo em minutos entre sincronizações automáticas';


--
-- Name: prest_marketplace_pedido; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_marketplace_pedido (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    marketplace character varying(50) NOT NULL,
    marketplace_pedido_id character varying(255) NOT NULL,
    cliente_nome character varying(255),
    cliente_email character varying(255),
    cliente_telefone character varying(50),
    cliente_documento character varying(20),
    endereco_completo text,
    endereco_cep character varying(10),
    endereco_cidade character varying(100),
    endereco_estado character varying(2),
    valor_total numeric(10,2) NOT NULL,
    valor_frete numeric(10,2) DEFAULT 0,
    valor_desconto numeric(10,2) DEFAULT 0,
    valor_produtos numeric(10,2) NOT NULL,
    status character varying(50),
    status_pagamento character varying(50),
    status_envio character varying(50),
    codigo_rastreio character varying(100),
    transportadora character varying(100),
    data_envio timestamp without time zone,
    data_entrega_prevista timestamp without time zone,
    venda_id uuid,
    importado boolean DEFAULT false,
    erro_importacao text,
    dados_completos jsonb,
    data_pedido timestamp without time zone NOT NULL,
    data_importacao timestamp without time zone DEFAULT now(),
    data_atualizacao timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_marketplace_pedido OWNER TO postgres;

--
-- Name: TABLE prest_marketplace_pedido; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_marketplace_pedido IS 'Pedidos importados dos marketplaces';


--
-- Name: COLUMN prest_marketplace_pedido.marketplace_pedido_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_pedido.marketplace_pedido_id IS 'ID do pedido no marketplace';


--
-- Name: COLUMN prest_marketplace_pedido.venda_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_pedido.venda_id IS 'ID da venda criada no sistema local a partir deste pedido';


--
-- Name: COLUMN prest_marketplace_pedido.importado; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_pedido.importado IS 'Se o pedido já foi convertido em venda no sistema local';


--
-- Name: prest_marketplace_pedido_item; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_marketplace_pedido_item (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    pedido_id uuid NOT NULL,
    marketplace_produto_id character varying(255),
    produto_id uuid,
    titulo character varying(255) NOT NULL,
    quantidade integer NOT NULL,
    preco_unitario numeric(10,2) NOT NULL,
    preco_total numeric(10,2) NOT NULL,
    sku character varying(100),
    variacao character varying(255),
    dados_completos jsonb,
    data_criacao timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_marketplace_pedido_item OWNER TO postgres;

--
-- Name: TABLE prest_marketplace_pedido_item; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_marketplace_pedido_item IS 'Itens dos pedidos importados dos marketplaces';


--
-- Name: COLUMN prest_marketplace_pedido_item.marketplace_produto_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_pedido_item.marketplace_produto_id IS 'ID do produto no marketplace';


--
-- Name: COLUMN prest_marketplace_pedido_item.produto_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_pedido_item.produto_id IS 'ID do produto local vinculado (se encontrado)';


--
-- Name: prest_marketplace_produto; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_marketplace_produto (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    produto_id uuid NOT NULL,
    marketplace character varying(50) NOT NULL,
    marketplace_produto_id character varying(255) NOT NULL,
    titulo_marketplace character varying(255),
    descricao_marketplace text,
    preco_marketplace numeric(10,2),
    estoque_marketplace integer,
    sku_marketplace character varying(100),
    url_marketplace text,
    categoria_marketplace character varying(255),
    status character varying(20) DEFAULT 'ATIVO'::character varying,
    ultima_sync timestamp without time zone,
    erro_sync text,
    dados_completos jsonb,
    data_criacao timestamp without time zone DEFAULT now(),
    data_atualizacao timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_marketplace_produto OWNER TO postgres;

--
-- Name: TABLE prest_marketplace_produto; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_marketplace_produto IS 'Vínculo entre produtos locais e produtos nos marketplaces';


--
-- Name: COLUMN prest_marketplace_produto.marketplace_produto_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_produto.marketplace_produto_id IS 'ID do produto no marketplace (ex: MLB123456789)';


--
-- Name: COLUMN prest_marketplace_produto.status; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_produto.status IS 'Status do produto: ATIVO, PAUSADO, ERRO, REMOVIDO';


--
-- Name: prest_marketplace_sync_log; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_marketplace_sync_log (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    marketplace character varying(50) NOT NULL,
    tipo_sync character varying(50) NOT NULL,
    status character varying(20) NOT NULL,
    itens_processados integer DEFAULT 0,
    itens_sucesso integer DEFAULT 0,
    itens_erro integer DEFAULT 0,
    mensagem text,
    detalhes jsonb,
    tempo_execucao_ms integer,
    data_inicio timestamp without time zone DEFAULT now(),
    data_fim timestamp without time zone
);


ALTER TABLE public.prest_marketplace_sync_log OWNER TO postgres;

--
-- Name: TABLE prest_marketplace_sync_log; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_marketplace_sync_log IS 'Logs de sincronização com marketplaces';


--
-- Name: COLUMN prest_marketplace_sync_log.tipo_sync; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_sync_log.tipo_sync IS 'Tipo de sincronização: PRODUTOS, ESTOQUE, PEDIDOS, WEBHOOK';


--
-- Name: COLUMN prest_marketplace_sync_log.status; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_marketplace_sync_log.status IS 'Resultado: SUCESSO, ERRO, PARCIAL';


--
-- Name: prest_orcamento_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_orcamento_itens (
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

CREATE TABLE IF NOT EXISTS public.prest_orcamentos (
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
-- Name: prest_parcelas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_parcelas (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    venda_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    numero_parcela integer NOT NULL,
    valor_parcela numeric(10,2) NOT NULL,
    data_vencimento date NOT NULL,
    status_parcela_codigo character varying(20) DEFAULT 'PENDENTE'::character varying NOT NULL,
    data_pagamento date,
    valor_pago numeric(10,2),
    observacoes text,
    forma_pagamento_id uuid,
    cobrador_id uuid,
    carteira_cobranca_id uuid,
    id_integracao_externa character varying(255)
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
-- Name: prest_periodos_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_periodos_cobranca (
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
-- Name: prest_produto_cards; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_produto_cards (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    produto_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    formato character varying(20) NOT NULL,
    card_path character varying(500) NOT NULL,
    card_url character varying(500),
    metadata jsonb,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT prest_produto_cards_formato_check CHECK (((formato)::text = ANY ((ARRAY['feed'::character varying, 'stories'::character varying])::text[])))
);


ALTER TABLE public.prest_produto_cards OWNER TO postgres;

--
-- Name: prest_produto_fotos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_produto_fotos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    produto_id uuid NOT NULL,
    arquivo_nome character varying(255) NOT NULL,
    arquivo_path character varying(500) NOT NULL,
    eh_principal boolean DEFAULT false NOT NULL,
    ordem integer DEFAULT 0 NOT NULL,
    data_upload timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_produto_fotos OWNER TO postgres;

--
-- Name: prest_produto_kit_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_produto_kit_itens (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    kit_id uuid NOT NULL,
    produto_id uuid NOT NULL,
    quantidade numeric(15,3) DEFAULT 1.000 NOT NULL,
    data_criacao timestamp with time zone DEFAULT now()
);


ALTER TABLE public.prest_produto_kit_itens OWNER TO postgres;

--
-- Name: prest_produtos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_produtos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    nome character varying(150) NOT NULL,
    descricao text,
    codigo_referencia character varying(50),
    preco_custo numeric(10,2) DEFAULT 0.00 NOT NULL,
    preco_venda_sugerido numeric(10,2) NOT NULL,
    estoque_atual integer DEFAULT 0 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    categoria_id uuid,
    permite_parcelamento boolean DEFAULT false NOT NULL,
    preco_promocional numeric(10,2),
    data_inicio_promocao timestamp with time zone,
    data_fim_promocao timestamp with time zone,
    valor_frete numeric(10,2) DEFAULT 0 NOT NULL,
    margem_lucro_percentual numeric(5,2) DEFAULT NULL::numeric,
    markup_percentual numeric(10,2) DEFAULT NULL::numeric,
    estoque_minimo integer DEFAULT 10 NOT NULL,
    ponto_corte integer DEFAULT 5 NOT NULL,
    localizacao character varying(30) DEFAULT NULL::character varying,
    codigo_barras character varying(255),
    marca character varying(255),
    estoque_maximo integer,
    unidade_medida character varying(255),
    venda_fracionada boolean,
    com_nota boolean,
    qtd_escala_1 numeric(10,3),
    preco_escala_1 numeric(10,2),
    qtd_escala_2 numeric(10,3),
    preco_escala_2 numeric(10,2),
    qtd_escala_3 numeric(10,3),
    preco_escala_3 numeric(10,2),
    qtd_escala_4 numeric(10,3),
    preco_escala_4 numeric(10,2),
    qtd_escala_5 numeric(10,3),
    preco_escala_5 numeric(10,2),
    cor character varying(255),
    eh_kit boolean,
    parent_id uuid,
    porte character varying(255),
    tamanho character varying(255),
    CONSTRAINT check_ponto_corte_maior_igual_minimo CHECK ((ponto_corte >= estoque_minimo))
);


ALTER TABLE public.prest_produtos OWNER TO postgres;

--
-- Name: TABLE prest_produtos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_produtos IS 'Cadastro de produtos de cada prestanista.';


--
-- Name: COLUMN prest_produtos.codigo_referencia; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.codigo_referencia IS 'Código de referência único por usuário. Deve ser único para cada prestanista.';


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
-- Name: COLUMN prest_produtos.valor_frete; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.valor_frete IS 'Valor do frete do produto em R$';


--
-- Name: COLUMN prest_produtos.margem_lucro_percentual; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.margem_lucro_percentual IS 'Margem de lucro em percentual calculada sobre o preço de venda: ((Preço Venda - Custo) / Preço Venda) * 100';


--
-- Name: COLUMN prest_produtos.markup_percentual; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.markup_percentual IS 'Markup em percentual calculado sobre o custo: ((Preço Venda - Custo) / Custo) * 100. Suporta valores até 99.999.999,99%.';


--
-- Name: COLUMN prest_produtos.estoque_minimo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.estoque_minimo IS 'Estoque mínimo desejado para o produto. Quando o estoque atual ficar abaixo deste valor, será exibido alerta.';


--
-- Name: COLUMN prest_produtos.ponto_corte; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.ponto_corte IS 'Ponto de corte (reorder point). Deve ser maior ou igual ao estoque mínimo. Quando o estoque atual chegar neste valor, é recomendado fazer resuprimento urgente.';


--
-- Name: COLUMN prest_produtos.localizacao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_produtos.localizacao IS 'Localização física onde o produto está armazenado (ex: Prateleira A3, Estoque 2, etc.)';


--
-- Name: prest_regioes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_regioes (
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
-- Name: prest_regras_parcelamento; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_regras_parcelamento (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    min_parcelas integer NOT NULL,
    max_parcelas integer NOT NULL,
    percentual_acrescimo numeric(5,2) DEFAULT 0.00 NOT NULL,
    descricao character varying(255),
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_regras_max_maior_min CHECK ((max_parcelas >= min_parcelas)),
    CONSTRAINT ck_regras_max_parcelas_positivo CHECK ((max_parcelas >= 1)),
    CONSTRAINT ck_regras_min_parcelas_positivo CHECK ((min_parcelas >= 1)),
    CONSTRAINT ck_regras_percentual_valido CHECK (((percentual_acrescimo >= (0)::numeric) AND (percentual_acrescimo <= 999.99)))
);


ALTER TABLE public.prest_regras_parcelamento OWNER TO postgres;

--
-- Name: TABLE prest_regras_parcelamento; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_regras_parcelamento IS 'Armazena as regras de acréscimo percentual por faixa de parcelamento para cada usuário/loja';


--
-- Name: COLUMN prest_regras_parcelamento.id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.id IS 'Identificador único da regra (UUID)';


--
-- Name: COLUMN prest_regras_parcelamento.usuario_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.usuario_id IS 'ID do usuário/loja proprietário da regra';


--
-- Name: COLUMN prest_regras_parcelamento.min_parcelas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.min_parcelas IS 'Número mínimo de parcelas para aplicar esta regra';


--
-- Name: COLUMN prest_regras_parcelamento.max_parcelas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.max_parcelas IS 'Número máximo de parcelas para aplicar esta regra';


--
-- Name: COLUMN prest_regras_parcelamento.percentual_acrescimo; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.percentual_acrescimo IS 'Percentual de acréscimo a ser aplicado (ex: 10.00 = 10%)';


--
-- Name: COLUMN prest_regras_parcelamento.descricao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.descricao IS 'Descrição opcional da regra (ex: "Curto Prazo", "Médio Prazo")';


--
-- Name: COLUMN prest_regras_parcelamento.data_criacao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.data_criacao IS 'Data e hora de criação do registro';


--
-- Name: COLUMN prest_regras_parcelamento.data_atualizacao; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_regras_parcelamento.data_atualizacao IS 'Data e hora da última atualização do registro';


--
-- Name: prest_rotas_cobranca; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_rotas_cobranca (
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
-- Name: prest_status_parcela; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_status_parcela (
    codigo character varying(20) NOT NULL,
    descricao character varying(100) NOT NULL
);


ALTER TABLE public.prest_status_parcela OWNER TO postgres;

--
-- Name: prest_status_venda; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_status_venda (
    codigo character varying(20) NOT NULL,
    descricao character varying(100) NOT NULL
);


ALTER TABLE public.prest_status_venda OWNER TO postgres;

--
-- Name: prest_taxas_entrega; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_taxas_entrega (
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

CREATE TABLE IF NOT EXISTS public.prest_tipos_despesa (
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
-- Name: prest_unidade_medida_volume; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_unidade_medida_volume (
    nome character varying(10) NOT NULL,
    descricao character varying(100) NOT NULL,
    ativo boolean DEFAULT true
);


ALTER TABLE public.prest_unidade_medida_volume OWNER TO postgres;

--
-- Name: prest_usuarios; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_usuarios (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    nome character varying(100) NOT NULL,
    email character varying(100),
    hash_senha character varying(255) NOT NULL,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    cpf character varying(20) NOT NULL,
    telefone character varying(30) NOT NULL,
    auth_key character varying(32),
    api_de_pagamento boolean DEFAULT false NOT NULL,
    mercadopago_public_key character varying(255),
    mercadopago_access_token character varying(255),
    mercadopago_sandbox boolean DEFAULT true,
    asaas_api_key character varying(255),
    asaas_sandbox boolean DEFAULT true,
    gateway_pagamento character varying(50) DEFAULT 'nenhum'::character varying,
    catalogo_path character varying(100) DEFAULT 'catalogo'::character varying,
    endereco character varying(255),
    bairro character varying(100),
    cidade character varying(100),
    estado character varying(2),
    logo_path character varying(500),
    username character varying(50) NOT NULL,
    eh_dono_loja boolean DEFAULT true NOT NULL,
    blocked_at timestamp without time zone,
    confirmed_at timestamp without time zone,
    mp_access_token text,
    mp_refresh_token text,
    mp_public_key character varying(255) DEFAULT NULL::character varying,
    mp_user_id character varying(50) DEFAULT NULL::character varying,
    mp_token_expiration timestamp(0) without time zone DEFAULT NULL::timestamp without time zone,
    taxa_comissao numeric(10,2),
    status_loja character varying(255),
    is_admin boolean
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
-- Name: COLUMN prest_usuarios.endereco; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.endereco IS 'Endereço (logradouro) da empresa/loja';


--
-- Name: COLUMN prest_usuarios.bairro; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.bairro IS 'Bairro da empresa/loja';


--
-- Name: COLUMN prest_usuarios.cidade; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.cidade IS 'Cidade da empresa/loja';


--
-- Name: COLUMN prest_usuarios.estado; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.estado IS 'Estado (UF) da empresa/loja';


--
-- Name: COLUMN prest_usuarios.logo_path; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.logo_path IS 'Caminho/URL da logo da empresa para uso em comprovantes e documentos';


--
-- Name: COLUMN prest_usuarios.username; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.username IS 'Nome de usuário único para login (pode ser email ou CPF)';


--
-- Name: COLUMN prest_usuarios.eh_dono_loja; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.eh_dono_loja IS 'Flag que indica se o usuário é dono da loja (true) ou colaborador (false)';


--
-- Name: COLUMN prest_usuarios.blocked_at; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.blocked_at IS 'Data/hora em que o usuário foi bloqueado. NULL = usuário ativo, não NULL = usuário bloqueado';


--
-- Name: COLUMN prest_usuarios.confirmed_at; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.confirmed_at IS 'Data/hora em que o email foi confirmado. NULL = não confirmado, não NULL = confirmado';


--
-- Name: COLUMN prest_usuarios.mp_access_token; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.mp_access_token IS 'Access token do vendedor obtido via OAuth do Mercado Pago';


--
-- Name: COLUMN prest_usuarios.mp_refresh_token; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.mp_refresh_token IS 'Refresh token do vendedor obtido via OAuth do Mercado Pago';


--
-- Name: COLUMN prest_usuarios.mp_public_key; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.mp_public_key IS 'Public key do vendedor (Checkout/Pix) obtida via OAuth';


--
-- Name: COLUMN prest_usuarios.mp_user_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.mp_user_id IS 'Identificador do usuário Mercado Pago (seller_id)';


--
-- Name: COLUMN prest_usuarios.mp_token_expiration; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_usuarios.mp_token_expiration IS 'Data/hora de expiração do access token do vendedor';


--
-- Name: prest_venda_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_venda_itens (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    venda_id uuid NOT NULL,
    produto_id uuid NOT NULL,
    quantidade integer NOT NULL,
    preco_unitario_venda numeric(10,2) NOT NULL,
    valor_total_item numeric(10,2) NOT NULL,
    desconto_percentual numeric(10,2) DEFAULT 0.00,
    desconto_valor numeric(10,2) DEFAULT 0.00,
    nome_item_manual character varying(255),
    avulso_resolvido boolean
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

CREATE TABLE IF NOT EXISTS public.prest_vendas (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    cliente_id uuid,
    data_venda timestamp with time zone DEFAULT now() NOT NULL,
    valor_total numeric(10,2) NOT NULL,
    numero_parcelas integer DEFAULT 1 NOT NULL,
    status_venda_codigo character varying(50) DEFAULT 'EM_ABERTO'::character varying NOT NULL,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    colaborador_vendedor_id uuid,
    data_primeiro_vencimento date,
    forma_pagamento_id uuid,
    acrescimo_tipo character varying(50),
    observacao_acrescimo text,
    acrescimo_valor numeric(10,2),
    cpf_consumidor character varying(255)
);


ALTER TABLE public.prest_vendas OWNER TO postgres;

--
-- Name: TABLE prest_vendas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_vendas IS 'Registra o cabeçalho de cada venda, associando um cliente e um valor total.';


--
-- Name: COLUMN prest_vendas.cliente_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_vendas.cliente_id IS 'ID do cliente. NULL para vendas diretas (loja física sem cliente cadastrado).';


--
-- Name: COLUMN prest_vendas.valor_total; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_vendas.valor_total IS 'Soma total dos itens da venda.';


--
-- Name: COLUMN prest_vendas.numero_parcelas; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.prest_vendas.numero_parcelas IS 'Quantidade de parcelas acordadas com o cliente.';


--
-- Name: prest_vendedores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.prest_vendedores (
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
-- Name: profile; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.profile (
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
-- Name: pulse_whatsapp_config; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.pulse_whatsapp_config (
    id integer NOT NULL,
    empresa_id uuid NOT NULL,
    instance_name character varying(255) NOT NULL,
    token character varying(255) DEFAULT ''::character varying NOT NULL,
    status character varying(50) DEFAULT 'DISCONNECTED'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    delay_min integer DEFAULT 1500 NOT NULL,
    delay_max integer DEFAULT 2500 NOT NULL,
    simular_digitacao smallint DEFAULT 1 NOT NULL
);


ALTER TABLE public.pulse_whatsapp_config OWNER TO postgres;

--
-- Name: pulse_whatsapp_config_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pulse_whatsapp_config_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pulse_whatsapp_config_id_seq OWNER TO postgres;

--
-- Name: pulse_whatsapp_config_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pulse_whatsapp_config_id_seq OWNED BY public.pulse_whatsapp_config.id;


--
-- Name: saas_financial_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.saas_financial_logs (
    id bigint NOT NULL,
    tenant_id uuid NOT NULL,
    order_id uuid NOT NULL,
    mp_payment_id character varying(100),
    total_amount numeric(12,2) NOT NULL,
    platform_fee numeric(12,2) NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.saas_financial_logs OWNER TO postgres;

--
-- Name: TABLE saas_financial_logs; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.saas_financial_logs IS 'Auditoria financeira das comissões da plataforma (split Mercado Pago).';


--
-- Name: COLUMN saas_financial_logs.platform_fee; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.saas_financial_logs.platform_fee IS 'Valor da comissão da plataforma retida na transação.';


--
-- Name: saas_financial_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.saas_financial_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.saas_financial_logs_id_seq OWNER TO postgres;

--
-- Name: saas_financial_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.saas_financial_logs_id_seq OWNED BY public.saas_financial_logs.id;


--
-- Name: servico_adm_contas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.servico_adm_contas (
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

CREATE TABLE IF NOT EXISTS public.servico_catalogo_categorias (
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

CREATE TABLE IF NOT EXISTS public.servico_catalogo_produto_categoria_assoc (
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

CREATE TABLE IF NOT EXISTS public.servico_catalogo_produto_imagens (
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

CREATE TABLE IF NOT EXISTS public.servico_clientes (
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

CREATE TABLE IF NOT EXISTS public.servico_contas_pagar (
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

CREATE TABLE IF NOT EXISTS public.servico_contas_receber (
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

CREATE TABLE IF NOT EXISTS public.servico_empresas (
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

CREATE TABLE IF NOT EXISTS public.servico_etapas_producao (
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

CREATE TABLE IF NOT EXISTS public.servico_ficha_tecnica (
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

CREATE TABLE IF NOT EXISTS public.servico_lotes (
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

CREATE TABLE IF NOT EXISTS public.servico_materiais (
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

CREATE TABLE IF NOT EXISTS public.servico_movimentacoes_estoque (
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

CREATE TABLE IF NOT EXISTS public.servico_ordens_producao (
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

CREATE TABLE IF NOT EXISTS public.servico_pedido_venda_itens (
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

CREATE TABLE IF NOT EXISTS public.servico_pedidos_venda (
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

CREATE TABLE IF NOT EXISTS public.servico_produtos (
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

CREATE TABLE IF NOT EXISTS public.servico_qualidade_defeitos (
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

CREATE TABLE IF NOT EXISTS public.servico_status_conta_financeira (
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

CREATE TABLE IF NOT EXISTS public.servico_status_lote (
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

CREATE TABLE IF NOT EXISTS public.servico_status_ordem_producao (
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

CREATE TABLE IF NOT EXISTS public.servico_status_pedido_venda (
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

CREATE TABLE IF NOT EXISTS public.servico_terceiros (
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

CREATE TABLE IF NOT EXISTS public.servico_tipos_movimento_estoque (
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

CREATE TABLE IF NOT EXISTS public.servico_tipos_pessoa (
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
-- Name: sis_assinaturas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sis_assinaturas (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    plano_id uuid NOT NULL,
    status character varying(20) DEFAULT 'ativa'::character varying NOT NULL,
    data_inicio date DEFAULT CURRENT_DATE NOT NULL,
    data_fim date,
    data_cancelamento date,
    valor_pago numeric(10,2),
    forma_pagamento character varying(50),
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now(),
    data_atualizacao timestamp with time zone DEFAULT now(),
    CONSTRAINT sis_assinaturas_status_check CHECK (((status)::text = ANY (ARRAY[('ativa'::character varying)::text, ('trial'::character varying)::text, ('suspensa'::character varying)::text, ('cancelada'::character varying)::text, ('expirada'::character varying)::text])))
);


ALTER TABLE public.sis_assinaturas OWNER TO postgres;

--
-- Name: sis_modulos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sis_modulos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    codigo character varying(50) NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    icone character varying(50),
    cor character varying(20),
    rota character varying(100) NOT NULL,
    ativo boolean DEFAULT true,
    ordem integer DEFAULT 0,
    data_criacao timestamp with time zone DEFAULT now(),
    data_atualizacao timestamp with time zone DEFAULT now()
);


ALTER TABLE public.sis_modulos OWNER TO postgres;

--
-- Name: sis_pagamentos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sis_pagamentos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    assinatura_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    valor numeric(10,2) NOT NULL,
    forma_pagamento character varying(50),
    status character varying(20) DEFAULT 'pendente'::character varying NOT NULL,
    data_pagamento date,
    comprovante text,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now(),
    CONSTRAINT sis_pagamentos_status_check CHECK (((status)::text = ANY (ARRAY[('pendente'::character varying)::text, ('aprovado'::character varying)::text, ('recusado'::character varying)::text, ('estornado'::character varying)::text])))
);


ALTER TABLE public.sis_pagamentos OWNER TO postgres;

--
-- Name: sis_plano_modulos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sis_plano_modulos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    plano_id uuid NOT NULL,
    modulo_id uuid NOT NULL,
    data_criacao timestamp with time zone DEFAULT now()
);


ALTER TABLE public.sis_plano_modulos OWNER TO postgres;

--
-- Name: sis_planos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sis_planos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    tipo character varying(20) NOT NULL,
    valor numeric(10,2) NOT NULL,
    dias_duracao integer,
    dias_trial integer DEFAULT 0,
    ativo boolean DEFAULT true,
    recursos jsonb,
    data_criacao timestamp with time zone DEFAULT now(),
    data_atualizacao timestamp with time zone DEFAULT now(),
    CONSTRAINT sis_planos_tipo_check CHECK (((tipo)::text = ANY (ARRAY[('mensal'::character varying)::text, ('anual'::character varying)::text, ('vitalicio'::character varying)::text, ('trial'::character varying)::text])))
);


ALTER TABLE public.sis_planos OWNER TO postgres;

--
-- Name: sis_usuario_modulos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sis_usuario_modulos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    modulo_id uuid NOT NULL,
    data_inicio date DEFAULT CURRENT_DATE NOT NULL,
    data_fim date,
    ativo boolean DEFAULT true,
    observacoes text,
    data_criacao timestamp with time zone DEFAULT now()
);


ALTER TABLE public.sis_usuario_modulos OWNER TO postgres;

--
-- Name: sys_modulos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public.sys_modulos (
    id integer NOT NULL,
    modulo character varying(250) NOT NULL,
    path character varying(250) NOT NULL,
    status boolean DEFAULT false NOT NULL
);


ALTER TABLE public.sys_modulos OWNER TO postgres;

--
-- Name: TABLE sys_modulos; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.sys_modulos IS 'Módulos disponíveis no sistema';


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

CREATE TABLE IF NOT EXISTS public.tab_form_login (
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

CREATE TABLE IF NOT EXISTS public.token (
    user_id integer NOT NULL,
    code character varying(32) NOT NULL,
    created_at integer NOT NULL,
    type smallint NOT NULL
);


ALTER TABLE public.token OWNER TO postgres;

--
-- Name: user; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE IF NOT EXISTS public."user" (
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
-- Name: vw_clientes_cobrador; Type: VIEW; Schema: public; Owner: postgres
--

CREATE OR REPLACE VIEW public.vw_clientes_cobrador AS
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

CREATE OR REPLACE VIEW public.vw_dashboard_cobrador AS
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

CREATE OR REPLACE VIEW public.vw_historico_compras_produto AS
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

CREATE OR REPLACE VIEW public.vw_parcelas_cobrador AS
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

CREATE OR REPLACE VIEW public.vw_parcelas_vencidas_cobrador AS
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

CREATE OR REPLACE VIEW public.vw_rota_dia_cobrador AS
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

CREATE OR REPLACE VIEW public.vw_usuario_modulos_disponiveis AS
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
-- Name: asaas_clientes id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_clientes ALTER COLUMN id SET DEFAULT nextval('public.asaas_clientes_id_seq'::regclass);


--
-- Name: asaas_cobrancas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_cobrancas ALTER COLUMN id SET DEFAULT nextval('public.asaas_cobrancas_id_seq'::regclass);


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
-- Name: mercadopago_preferencias id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mercadopago_preferencias ALTER COLUMN id SET DEFAULT nextval('public.mercadopago_preferencias_id_seq'::regclass);


--
-- Name: orcamento_itens id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orcamento_itens ALTER COLUMN id SET DEFAULT nextval('public.orcamento_itens_id_seq'::regclass);


--
-- Name: orcamentos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orcamentos ALTER COLUMN id SET DEFAULT nextval('public.orcamentos_id_seq'::regclass);


--
-- Name: prest_dados_financeiros id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_dados_financeiros ALTER COLUMN id SET DEFAULT nextval('public.prest_dados_financeiros_id_seq'::regclass);


--
-- Name: prest_financeiro_mensal id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_financeiro_mensal ALTER COLUMN id SET DEFAULT nextval('public.prest_financeiro_mensal_id_seq'::regclass);


--
-- Name: pulse_whatsapp_config id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pulse_whatsapp_config ALTER COLUMN id SET DEFAULT nextval('public.pulse_whatsapp_config_id_seq'::regclass);


--
-- Name: saas_financial_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.saas_financial_logs ALTER COLUMN id SET DEFAULT nextval('public.saas_financial_logs_id_seq'::regclass);


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
-- Name: asaas_clientes asaas_clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_clientes
    ADD CONSTRAINT asaas_clientes_pkey PRIMARY KEY (id);


--
-- Name: asaas_cobrancas asaas_cobrancas_payment_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_cobrancas
    ADD CONSTRAINT asaas_cobrancas_payment_id_key UNIQUE (payment_id);


--
-- Name: asaas_cobrancas asaas_cobrancas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_cobrancas
    ADD CONSTRAINT asaas_cobrancas_pkey PRIMARY KEY (id);


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
-- Name: loja_configuracao loja_configuracao_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.loja_configuracao
    ADD CONSTRAINT loja_configuracao_pkey PRIMARY KEY (id);


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
-- Name: mercadopago_preferencias mercadopago_preferencias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mercadopago_preferencias
    ADD CONSTRAINT mercadopago_preferencias_pkey PRIMARY KEY (id);


--
-- Name: migration migration_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migration
    ADD CONSTRAINT migration_pkey PRIMARY KEY (version);


--
-- Name: orcamento_itens orcamento_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orcamento_itens
    ADD CONSTRAINT orcamento_itens_pkey PRIMARY KEY (id);


--
-- Name: orcamentos orcamentos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.orcamentos
    ADD CONSTRAINT orcamentos_pkey PRIMARY KEY (id);


--
-- Name: prest_regras_parcelamento pk_prest_regras_parcelamento; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_regras_parcelamento
    ADD CONSTRAINT pk_prest_regras_parcelamento PRIMARY KEY (id);


--
-- Name: prest_caixa_movimentacoes prest_caixa_movimentacoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa_movimentacoes
    ADD CONSTRAINT prest_caixa_movimentacoes_pkey PRIMARY KEY (id);


--
-- Name: prest_caixa prest_caixa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa
    ADD CONSTRAINT prest_caixa_pkey PRIMARY KEY (id);


--
-- Name: prest_carteira_cobranca prest_carteira_cobranca_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_cobranca_pkey PRIMARY KEY (id);


--
-- Name: prest_carteira_cobranca prest_carteira_unica_por_periodo; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_unica_por_periodo UNIQUE (periodo_id, cliente_id);


--
-- Name: prest_categorias prest_categorias_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_categorias
    ADD CONSTRAINT prest_categorias_pkey PRIMARY KEY (id);


--
-- Name: prest_clientes prest_clientes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_pkey PRIMARY KEY (id);


--
-- Name: prest_clientes prest_clientes_unique_loja; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_unique_loja UNIQUE (usuario_id, cpf);


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
-- Name: prest_colaboradores prest_colaboradores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_colaboradores
    ADD CONSTRAINT prest_colaboradores_pkey PRIMARY KEY (id);


--
-- Name: prest_comissao_config prest_comissao_config_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissao_config
    ADD CONSTRAINT prest_comissao_config_pkey PRIMARY KEY (id);


--
-- Name: prest_comissoes prest_comissoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissoes
    ADD CONSTRAINT prest_comissoes_pkey PRIMARY KEY (id);


--
-- Name: prest_compras prest_compras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_compras
    ADD CONSTRAINT prest_compras_pkey PRIMARY KEY (id);


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
-- Name: prest_cupons_fiscais prest_cupons_fiscais_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cupons_fiscais
    ADD CONSTRAINT prest_cupons_fiscais_pkey PRIMARY KEY (id);


--
-- Name: prest_dados_financeiros prest_dados_financeiros_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_dados_financeiros
    ADD CONSTRAINT prest_dados_financeiros_pkey PRIMARY KEY (id);


--
-- Name: prest_disparo_itens prest_disparo_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_disparo_itens
    ADD CONSTRAINT prest_disparo_itens_pkey PRIMARY KEY (id);


--
-- Name: prest_disparos_massa prest_disparos_massa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_disparos_massa
    ADD CONSTRAINT prest_disparos_massa_pkey PRIMARY KEY (id);


--
-- Name: prest_dispositivos_pagamento prest_dispositivos_pagamento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_dispositivos_pagamento
    ADD CONSTRAINT prest_dispositivos_pagamento_pkey PRIMARY KEY (id);


--
-- Name: prest_estoque_movimentacoes prest_estoque_movimentacoes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_estoque_movimentacoes
    ADD CONSTRAINT prest_estoque_movimentacoes_pkey PRIMARY KEY (id);


--
-- Name: prest_financeiro_mensal prest_financeiro_mensal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_financeiro_mensal
    ADD CONSTRAINT prest_financeiro_mensal_pkey PRIMARY KEY (id);


--
-- Name: prest_formas_pagamento prest_formas_pagamento_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_formas_pagamento
    ADD CONSTRAINT prest_formas_pagamento_pkey PRIMARY KEY (id);


--
-- Name: prest_fornecedores prest_fornecedores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_fornecedores
    ADD CONSTRAINT prest_fornecedores_pkey PRIMARY KEY (id);


--
-- Name: prest_historico_cobranca prest_historico_cobranca_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_historico_cobranca
    ADD CONSTRAINT prest_historico_cobranca_pkey PRIMARY KEY (id);


--
-- Name: prest_itens_compra prest_itens_compra_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_itens_compra
    ADD CONSTRAINT prest_itens_compra_pkey PRIMARY KEY (id);


--
-- Name: prest_marketplace_config prest_marketplace_config_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_config
    ADD CONSTRAINT prest_marketplace_config_pkey PRIMARY KEY (id);


--
-- Name: prest_marketplace_config prest_marketplace_config_usuario_id_marketplace_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_config
    ADD CONSTRAINT prest_marketplace_config_usuario_id_marketplace_key UNIQUE (usuario_id, marketplace);


--
-- Name: prest_marketplace_pedido_item prest_marketplace_pedido_item_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido_item
    ADD CONSTRAINT prest_marketplace_pedido_item_pkey PRIMARY KEY (id);


--
-- Name: prest_marketplace_pedido prest_marketplace_pedido_marketplace_marketplace_pedido_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido
    ADD CONSTRAINT prest_marketplace_pedido_marketplace_marketplace_pedido_id_key UNIQUE (marketplace, marketplace_pedido_id);


--
-- Name: prest_marketplace_pedido prest_marketplace_pedido_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido
    ADD CONSTRAINT prest_marketplace_pedido_pkey PRIMARY KEY (id);


--
-- Name: prest_marketplace_produto prest_marketplace_produto_marketplace_marketplace_produto_i_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_produto
    ADD CONSTRAINT prest_marketplace_produto_marketplace_marketplace_produto_i_key UNIQUE (marketplace, marketplace_produto_id);


--
-- Name: prest_marketplace_produto prest_marketplace_produto_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_produto
    ADD CONSTRAINT prest_marketplace_produto_pkey PRIMARY KEY (id);


--
-- Name: prest_marketplace_produto prest_marketplace_produto_produto_id_marketplace_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_produto
    ADD CONSTRAINT prest_marketplace_produto_produto_id_marketplace_key UNIQUE (produto_id, marketplace);


--
-- Name: prest_marketplace_sync_log prest_marketplace_sync_log_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_sync_log
    ADD CONSTRAINT prest_marketplace_sync_log_pkey PRIMARY KEY (id);


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
-- Name: prest_parcelas prest_parcelas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_pkey PRIMARY KEY (id);


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
-- Name: prest_produto_cards prest_produto_cards_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_cards
    ADD CONSTRAINT prest_produto_cards_pkey PRIMARY KEY (id);


--
-- Name: prest_produto_fotos prest_produto_fotos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_fotos
    ADD CONSTRAINT prest_produto_fotos_pkey PRIMARY KEY (id);


--
-- Name: prest_produto_kit_itens prest_produto_kit_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_kit_itens
    ADD CONSTRAINT prest_produto_kit_itens_pkey PRIMARY KEY (id);


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
-- Name: prest_unidade_medida_volume prest_unidade_medida_volume_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_unidade_medida_volume
    ADD CONSTRAINT prest_unidade_medida_volume_pkey PRIMARY KEY (nome);


--
-- Name: prest_usuarios prest_usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_usuarios
    ADD CONSTRAINT prest_usuarios_pkey PRIMARY KEY (id);


--
-- Name: prest_usuarios prest_usuarios_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_usuarios
    ADD CONSTRAINT prest_usuarios_unique UNIQUE (cpf);


--
-- Name: prest_usuarios prest_usuarios_username_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_usuarios
    ADD CONSTRAINT prest_usuarios_username_unique UNIQUE (username);


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
-- Name: prest_vendedores prest_vendedores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendedores
    ADD CONSTRAINT prest_vendedores_pkey PRIMARY KEY (id);


--
-- Name: profile profile_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profile
    ADD CONSTRAINT profile_pkey PRIMARY KEY (user_id);


--
-- Name: pulse_whatsapp_config pulse_whatsapp_config_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pulse_whatsapp_config
    ADD CONSTRAINT pulse_whatsapp_config_pkey PRIMARY KEY (id);


--
-- Name: saas_financial_logs saas_financial_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.saas_financial_logs
    ADD CONSTRAINT saas_financial_logs_pkey PRIMARY KEY (id);


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
-- Name: sis_assinaturas sis_assinaturas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_assinaturas
    ADD CONSTRAINT sis_assinaturas_pkey PRIMARY KEY (id);


--
-- Name: sis_modulos sis_modulos_codigo_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_modulos
    ADD CONSTRAINT sis_modulos_codigo_key UNIQUE (codigo);


--
-- Name: sis_modulos sis_modulos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_modulos
    ADD CONSTRAINT sis_modulos_pkey PRIMARY KEY (id);


--
-- Name: sis_pagamentos sis_pagamentos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_pagamentos
    ADD CONSTRAINT sis_pagamentos_pkey PRIMARY KEY (id);


--
-- Name: sis_plano_modulos sis_plano_modulos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_plano_modulos
    ADD CONSTRAINT sis_plano_modulos_pkey PRIMARY KEY (id);


--
-- Name: sis_plano_modulos sis_plano_modulos_plano_id_modulo_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_plano_modulos
    ADD CONSTRAINT sis_plano_modulos_plano_id_modulo_id_key UNIQUE (plano_id, modulo_id);


--
-- Name: sis_planos sis_planos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_planos
    ADD CONSTRAINT sis_planos_pkey PRIMARY KEY (id);


--
-- Name: sis_usuario_modulos sis_usuario_modulos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_usuario_modulos
    ADD CONSTRAINT sis_usuario_modulos_pkey PRIMARY KEY (id);


--
-- Name: sis_usuario_modulos sis_usuario_modulos_usuario_id_modulo_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_usuario_modulos
    ADD CONSTRAINT sis_usuario_modulos_usuario_id_modulo_id_key UNIQUE (usuario_id, modulo_id);


--
-- Name: sys_modulos sys_modulos_pk; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sys_modulos
    ADD CONSTRAINT sys_modulos_pk PRIMARY KEY (id);


--
-- Name: asaas_clientes uk_usuario_cpf; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_clientes
    ADD CONSTRAINT uk_usuario_cpf UNIQUE (usuario_id, cpf_cnpj);


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
-- Name: loja_configuracao uq_loja_config_usuario; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.loja_configuracao
    ADD CONSTRAINT uq_loja_config_usuario UNIQUE (usuario_id);


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
-- Name: pulse_whatsapp_config uq_whatsapp_config_empresa; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pulse_whatsapp_config
    ADD CONSTRAINT uq_whatsapp_config_empresa UNIQUE (empresa_id);


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
-- Name: idx-financeiro_mensal-usuario-mes; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX "idx-financeiro_mensal-usuario-mes" ON public.prest_financeiro_mensal USING btree (usuario_id, mes_referencia);


--
-- Name: idx_asaas_clientes_cpf; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_clientes_cpf ON public.asaas_clientes USING btree (cpf_cnpj);


--
-- Name: idx_asaas_clientes_customer; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_clientes_customer ON public.asaas_clientes USING btree (customer_asaas_id);


--
-- Name: idx_asaas_customer; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_customer ON public.asaas_cobrancas USING btree (customer_asaas_id);


--
-- Name: idx_asaas_external_ref; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_external_ref ON public.asaas_cobrancas USING btree (external_reference);


--
-- Name: idx_asaas_payment; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_payment ON public.asaas_cobrancas USING btree (payment_id);


--
-- Name: idx_asaas_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_status ON public.asaas_cobrancas USING btree (status_asaas);


--
-- Name: idx_asaas_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asaas_usuario ON public.asaas_cobrancas USING btree (usuario_id);


--
-- Name: idx_assinaturas_data_fim; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assinaturas_data_fim ON public.sis_assinaturas USING btree (data_fim);


--
-- Name: idx_assinaturas_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assinaturas_status ON public.sis_assinaturas USING btree (status);


--
-- Name: idx_assinaturas_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assinaturas_usuario ON public.sis_assinaturas USING btree (usuario_id);


--
-- Name: idx_carteira_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_carteira_ativo ON public.prest_carteira_cobranca USING btree (ativo) WHERE (ativo = true);


--
-- Name: idx_carteira_cliente_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_carteira_cliente_id ON public.prest_carteira_cobranca USING btree (cliente_id);


--
-- Name: idx_carteira_cobrador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_carteira_cobrador_id ON public.prest_carteira_cobranca USING btree (cobrador_id);


--
-- Name: idx_carteira_periodo_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_carteira_periodo_id ON public.prest_carteira_cobranca USING btree (periodo_id);


--
-- Name: idx_carteira_rota_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_carteira_rota_id ON public.prest_carteira_cobranca USING btree (rota_id);


--
-- Name: idx_categorias_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_categorias_ativo ON public.prest_categorias USING btree (ativo);


--
-- Name: idx_categorias_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_categorias_usuario_id ON public.prest_categorias USING btree (usuario_id);


--
-- Name: idx_clientes_nome; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_nome ON public.prest_clientes USING btree (nome_completo);


--
-- Name: idx_clientes_regiao_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_regiao_id ON public.prest_clientes USING btree (regiao_id);


--
-- Name: idx_clientes_telefone; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_telefone ON public.delivery_clientes USING btree (telefone);


--
-- Name: idx_clientes_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_clientes_usuario_id ON public.prest_clientes USING btree (usuario_id);


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
-- Name: idx_colaboradores_cobrador; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_colaboradores_cobrador ON public.prest_colaboradores USING btree (eh_cobrador) WHERE (eh_cobrador = true);


--
-- Name: idx_colaboradores_prest_usuario_login_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_colaboradores_prest_usuario_login_id ON public.prest_colaboradores USING btree (prest_usuario_login_id);


--
-- Name: idx_colaboradores_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_colaboradores_usuario_id ON public.prest_colaboradores USING btree (usuario_id);


--
-- Name: idx_colaboradores_vendedor; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_colaboradores_vendedor ON public.prest_colaboradores USING btree (eh_vendedor) WHERE (eh_vendedor = true);


--
-- Name: idx_comissao_config_busca; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissao_config_busca ON public.prest_comissao_config USING btree (usuario_id, colaborador_id, tipo_comissao, categoria_id, ativo);


--
-- Name: idx_comissao_config_categoria; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissao_config_categoria ON public.prest_comissao_config USING btree (categoria_id);


--
-- Name: idx_comissao_config_colaborador; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissao_config_colaborador ON public.prest_comissao_config USING btree (colaborador_id);


--
-- Name: idx_comissao_config_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissao_config_id ON public.prest_comissoes USING btree (comissao_config_id) WHERE (comissao_config_id IS NOT NULL);


--
-- Name: idx_comissao_config_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissao_config_usuario ON public.prest_comissao_config USING btree (usuario_id);


--
-- Name: idx_comissao_config_vigencia; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissao_config_vigencia ON public.prest_comissao_config USING btree (data_inicio, data_fim) WHERE ((data_inicio IS NOT NULL) OR (data_fim IS NOT NULL));


--
-- Name: idx_comissoes_colaborador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissoes_colaborador_id ON public.prest_comissoes USING btree (colaborador_id);


--
-- Name: idx_comissoes_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissoes_status ON public.prest_comissoes USING btree (status);


--
-- Name: idx_comissoes_tipo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comissoes_tipo ON public.prest_comissoes USING btree (tipo_comissao);


--
-- Name: idx_cupons_fiscais_chave; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_cupons_fiscais_chave ON public.prest_cupons_fiscais USING btree (chave_acesso);


--
-- Name: idx_cupons_fiscais_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cupons_fiscais_status ON public.prest_cupons_fiscais USING btree (status);


--
-- Name: idx_cupons_fiscais_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cupons_fiscais_usuario ON public.prest_cupons_fiscais USING btree (usuario_id);


--
-- Name: idx_cupons_fiscais_venda; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_cupons_fiscais_venda ON public.prest_cupons_fiscais USING btree (venda_id);


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
-- Name: idx_disparo_itens_disparo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_disparo_itens_disparo ON public.prest_disparo_itens USING btree (disparo_id);


--
-- Name: idx_disparo_itens_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_disparo_itens_status ON public.prest_disparo_itens USING btree (status);


--
-- Name: idx_disparos_massa_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_disparos_massa_status ON public.prest_disparos_massa USING btree (status);


--
-- Name: idx_disparos_massa_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_disparos_massa_usuario ON public.prest_disparos_massa USING btree (usuario_id);


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
-- Name: idx_financeiro_mensal_usuario_mes; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_financeiro_mensal_usuario_mes ON public.prest_financeiro_mensal USING btree (usuario_id, mes_referencia);


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
-- Name: idx_kit_itens_kit_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_kit_itens_kit_id ON public.prest_produto_kit_itens USING btree (kit_id);


--
-- Name: idx_marketplace_config_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_config_ativo ON public.prest_marketplace_config USING btree (ativo);


--
-- Name: idx_marketplace_config_marketplace; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_config_marketplace ON public.prest_marketplace_config USING btree (marketplace);


--
-- Name: idx_marketplace_config_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_config_usuario ON public.prest_marketplace_config USING btree (usuario_id);


--
-- Name: idx_marketplace_pedido_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_data ON public.prest_marketplace_pedido USING btree (data_pedido);


--
-- Name: idx_marketplace_pedido_importado; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_importado ON public.prest_marketplace_pedido USING btree (importado);


--
-- Name: idx_marketplace_pedido_item_pedido; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_item_pedido ON public.prest_marketplace_pedido_item USING btree (pedido_id);


--
-- Name: idx_marketplace_pedido_item_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_item_produto ON public.prest_marketplace_pedido_item USING btree (produto_id);


--
-- Name: idx_marketplace_pedido_marketplace; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_marketplace ON public.prest_marketplace_pedido USING btree (marketplace);


--
-- Name: idx_marketplace_pedido_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_status ON public.prest_marketplace_pedido USING btree (status);


--
-- Name: idx_marketplace_pedido_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_usuario ON public.prest_marketplace_pedido USING btree (usuario_id);


--
-- Name: idx_marketplace_pedido_venda; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_pedido_venda ON public.prest_marketplace_pedido USING btree (venda_id);


--
-- Name: idx_marketplace_produto_marketplace; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_produto_marketplace ON public.prest_marketplace_produto USING btree (marketplace);


--
-- Name: idx_marketplace_produto_marketplace_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_produto_marketplace_id ON public.prest_marketplace_produto USING btree (marketplace_produto_id);


--
-- Name: idx_marketplace_produto_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_produto_produto ON public.prest_marketplace_produto USING btree (produto_id);


--
-- Name: idx_marketplace_produto_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_produto_status ON public.prest_marketplace_produto USING btree (status);


--
-- Name: idx_marketplace_sync_log_data; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_sync_log_data ON public.prest_marketplace_sync_log USING btree (data_inicio);


--
-- Name: idx_marketplace_sync_log_marketplace; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_sync_log_marketplace ON public.prest_marketplace_sync_log USING btree (marketplace);


--
-- Name: idx_marketplace_sync_log_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_sync_log_status ON public.prest_marketplace_sync_log USING btree (status);


--
-- Name: idx_marketplace_sync_log_tipo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_sync_log_tipo ON public.prest_marketplace_sync_log USING btree (tipo_sync);


--
-- Name: idx_marketplace_sync_log_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_sync_log_usuario ON public.prest_marketplace_sync_log USING btree (usuario_id);


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
-- Name: idx_mp_external_ref; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mp_external_ref ON public.mercadopago_preferencias USING btree (external_reference);


--
-- Name: idx_mp_payment_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mp_payment_id ON public.mercadopago_preferencias USING btree (payment_id);


--
-- Name: idx_mp_preference; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mp_preference ON public.mercadopago_preferencias USING btree (preference_id);


--
-- Name: idx_mp_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mp_status ON public.mercadopago_preferencias USING btree (payment_status);


--
-- Name: idx_mp_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mp_usuario ON public.mercadopago_preferencias USING btree (usuario_id);


--
-- Name: idx_opcoes_desagregacao_id_categoria; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_opcoes_desagregacao_id_categoria ON public.ind_opcoes_desagregacao USING btree (id_categoria_desagregacao);


--
-- Name: idx_orcamento_itens_orcamento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamento_itens_orcamento ON public.orcamento_itens USING btree (orcamento_id);


--
-- Name: idx_orcamento_itens_orcamento_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamento_itens_orcamento_id ON public.prest_orcamento_itens USING btree (orcamento_id);


--
-- Name: idx_orcamentos_cliente; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamentos_cliente ON public.orcamentos USING btree (cliente_id);


--
-- Name: idx_orcamentos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamentos_status ON public.prest_orcamentos USING btree (status);


--
-- Name: idx_orcamentos_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamentos_usuario ON public.orcamentos USING btree (usuario_id);


--
-- Name: idx_orcamentos_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_orcamentos_usuario_id ON public.prest_orcamentos USING btree (usuario_id);


--
-- Name: idx_pagamentos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pagamentos_status ON public.sis_pagamentos USING btree (status);


--
-- Name: idx_pagamentos_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_pagamentos_usuario ON public.sis_pagamentos USING btree (usuario_id);


--
-- Name: idx_parcelas_carteira_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_parcelas_carteira_id ON public.prest_parcelas USING btree (carteira_cobranca_id);


--
-- Name: idx_parcelas_cobrador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_parcelas_cobrador_id ON public.prest_parcelas USING btree (cobrador_id);


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
-- Name: idx_periodos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_periodos_status ON public.prest_periodos_cobranca USING btree (status);


--
-- Name: idx_periodos_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_periodos_usuario_id ON public.prest_periodos_cobranca USING btree (usuario_id);


--
-- Name: idx_prest_caixa_colaborador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_colaborador_id ON public.prest_caixa USING btree (colaborador_id);


--
-- Name: idx_prest_caixa_data_abertura; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_data_abertura ON public.prest_caixa USING btree (data_abertura);


--
-- Name: idx_prest_caixa_movimentacoes_caixa_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_movimentacoes_caixa_id ON public.prest_caixa_movimentacoes USING btree (caixa_id);


--
-- Name: idx_prest_caixa_movimentacoes_data_movimento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_movimentacoes_data_movimento ON public.prest_caixa_movimentacoes USING btree (data_movimento);


--
-- Name: idx_prest_caixa_movimentacoes_parcela_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_movimentacoes_parcela_id ON public.prest_caixa_movimentacoes USING btree (parcela_id);


--
-- Name: idx_prest_caixa_movimentacoes_tipo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_movimentacoes_tipo ON public.prest_caixa_movimentacoes USING btree (tipo);


--
-- Name: idx_prest_caixa_movimentacoes_venda_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_movimentacoes_venda_id ON public.prest_caixa_movimentacoes USING btree (venda_id);


--
-- Name: idx_prest_caixa_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_status ON public.prest_caixa USING btree (status);


--
-- Name: idx_prest_caixa_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_caixa_usuario_id ON public.prest_caixa USING btree (usuario_id);


--
-- Name: idx_prest_clientes_cpf; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_clientes_cpf ON public.prest_clientes USING btree (cpf);


--
-- Name: idx_prest_clientes_cpf_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_clientes_cpf_usuario ON public.prest_clientes USING btree (cpf, usuario_id);


--
-- Name: idx_prest_compras_data_compra; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_compras_data_compra ON public.prest_compras USING btree (data_compra);


--
-- Name: idx_prest_compras_fornecedor_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_compras_fornecedor_id ON public.prest_compras USING btree (fornecedor_id);


--
-- Name: idx_prest_compras_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_compras_status ON public.prest_compras USING btree (status_compra);


--
-- Name: idx_prest_compras_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_compras_usuario_id ON public.prest_compras USING btree (usuario_id);


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
-- Name: idx_prest_dados_financeiros_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_dados_financeiros_produto ON public.prest_dados_financeiros USING btree (produto_id);


--
-- Name: idx_prest_dados_financeiros_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_dados_financeiros_usuario ON public.prest_dados_financeiros USING btree (usuario_id);


--
-- Name: idx_prest_dados_financeiros_usuario_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_prest_dados_financeiros_usuario_produto ON public.prest_dados_financeiros USING btree (usuario_id, produto_id);


--
-- Name: idx_prest_fornecedores_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_fornecedores_ativo ON public.prest_fornecedores USING btree (ativo);


--
-- Name: idx_prest_fornecedores_cnpj; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_fornecedores_cnpj ON public.prest_fornecedores USING btree (cnpj) WHERE (cnpj IS NOT NULL);


--
-- Name: idx_prest_fornecedores_cpf; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_fornecedores_cpf ON public.prest_fornecedores USING btree (cpf) WHERE (cpf IS NOT NULL);


--
-- Name: idx_prest_fornecedores_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_fornecedores_usuario_id ON public.prest_fornecedores USING btree (usuario_id);


--
-- Name: idx_prest_itens_compra_compra_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_itens_compra_compra_id ON public.prest_itens_compra USING btree (compra_id);


--
-- Name: idx_prest_itens_compra_produto_compra; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_itens_compra_produto_compra ON public.prest_itens_compra USING btree (produto_id, compra_id);


--
-- Name: idx_prest_itens_compra_produto_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_itens_compra_produto_id ON public.prest_itens_compra USING btree (produto_id);


--
-- Name: idx_prest_produto_cards_formato; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_produto_cards_formato ON public.prest_produto_cards USING btree (formato);


--
-- Name: idx_prest_produto_cards_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_produto_cards_produto ON public.prest_produto_cards USING btree (produto_id);


--
-- Name: idx_prest_produto_cards_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_produto_cards_usuario ON public.prest_produto_cards USING btree (usuario_id);


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
-- Name: idx_prest_usuarios_eh_dono_loja; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_usuarios_eh_dono_loja ON public.prest_usuarios USING btree (eh_dono_loja);


--
-- Name: idx_prest_usuarios_username; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_usuarios_username ON public.prest_usuarios USING btree (username);


--
-- Name: idx_produto_fotos_principal; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produto_fotos_principal ON public.prest_produto_fotos USING btree (eh_principal);


--
-- Name: idx_produto_fotos_produto_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produto_fotos_produto_id ON public.prest_produto_fotos USING btree (produto_id);


--
-- Name: idx_produtos_ativo_disponivel; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_ativo_disponivel ON public.delivery_produtos USING btree (ativo, disponivel) WHERE ((ativo = true) AND (disponivel = true));


--
-- Name: idx_produtos_categoria_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produtos_categoria_id ON public.prest_produtos USING btree (categoria_id);


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
-- Name: idx_regioes_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_regioes_usuario_id ON public.prest_regioes USING btree (usuario_id);


--
-- Name: idx_regras_parcelamento_faixa_parcelas; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_regras_parcelamento_faixa_parcelas ON public.prest_regras_parcelamento USING btree (usuario_id, min_parcelas, max_parcelas);


--
-- Name: idx_regras_parcelamento_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_regras_parcelamento_usuario_id ON public.prest_regras_parcelamento USING btree (usuario_id);


--
-- Name: idx_rotas_cobrador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_rotas_cobrador_id ON public.prest_rotas_cobranca USING btree (cobrador_id);


--
-- Name: idx_rotas_periodo_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_rotas_periodo_id ON public.prest_rotas_cobranca USING btree (periodo_id);


--
-- Name: idx_saas_fin_logs_order; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_fin_logs_order ON public.saas_financial_logs USING btree (order_id);


--
-- Name: idx_saas_fin_logs_payment; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_fin_logs_payment ON public.saas_financial_logs USING btree (mp_payment_id);


--
-- Name: idx_saas_fin_logs_tenant; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_fin_logs_tenant ON public.saas_financial_logs USING btree (tenant_id);


--
-- Name: idx_taxas_entrega_localidade; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_taxas_entrega_localidade ON public.prest_taxas_entrega USING btree (cidade, bairro, cep);


--
-- Name: idx_taxas_entrega_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_taxas_entrega_usuario_id ON public.prest_taxas_entrega USING btree (usuario_id);


--
-- Name: idx_usuario_modulos_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_usuario_modulos_ativo ON public.sis_usuario_modulos USING btree (ativo);


--
-- Name: idx_usuarios_status_loja; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_usuarios_status_loja ON public.prest_usuarios USING btree (status_loja);


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
-- Name: idx_vendas_colaborador_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_colaborador_id ON public.prest_vendas USING btree (colaborador_vendedor_id);


--
-- Name: idx_vendas_forma_pagamento_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_forma_pagamento_id ON public.prest_vendas USING btree (forma_pagamento_id);


--
-- Name: idx_vendas_status_codigo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_status_codigo ON public.prest_vendas USING btree (status_venda_codigo);


--
-- Name: idx_vendas_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendas_usuario_id ON public.prest_vendas USING btree (usuario_id);


--
-- Name: idx_vendedores_ativo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendedores_ativo ON public.prest_vendedores USING btree (ativo);


--
-- Name: idx_vendedores_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_vendedores_usuario_id ON public.prest_vendedores USING btree (usuario_id);


--
-- Name: idx_whatsapp_config_empresa; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_whatsapp_config_empresa ON public.pulse_whatsapp_config USING btree (empresa_id);


--
-- Name: token_unique; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX token_unique ON public.token USING btree (user_id, code, type);


--
-- Name: uq_saas_fin_logs_payment; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX uq_saas_fin_logs_payment ON public.saas_financial_logs USING btree (tenant_id, order_id, mp_payment_id);


--
-- Name: user_unique_email; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_unique_email ON public."user" USING btree (email);


--
-- Name: user_unique_username; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_unique_username ON public."user" USING btree (username);


--
-- Name: prest_categorias set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_categorias FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_categorias DISABLE TRIGGER set_timestamp;


--
-- Name: prest_clientes set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_clientes FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_clientes DISABLE TRIGGER set_timestamp;


--
-- Name: prest_colaboradores set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_colaboradores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_colaboradores DISABLE TRIGGER set_timestamp;


--
-- Name: prest_compras set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_compras FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_compras DISABLE TRIGGER set_timestamp;


--
-- Name: prest_configuracoes set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_configuracoes FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_configuracoes DISABLE TRIGGER set_timestamp;


--
-- Name: prest_fornecedores set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_fornecedores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_fornecedores DISABLE TRIGGER set_timestamp;


--
-- Name: prest_orcamentos set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_orcamentos FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_orcamentos DISABLE TRIGGER set_timestamp;


--
-- Name: prest_produtos set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_produtos FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_produtos DISABLE TRIGGER set_timestamp;


--
-- Name: prest_usuarios set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_usuarios FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_usuarios DISABLE TRIGGER set_timestamp;


--
-- Name: prest_vendas set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_vendas FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_vendas DISABLE TRIGGER set_timestamp;


--
-- Name: prest_vendedores set_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER set_timestamp BEFORE UPDATE ON public.prest_vendedores FOR EACH ROW EXECUTE FUNCTION public.trigger_set_timestamp();

ALTER TABLE public.prest_vendedores DISABLE TRIGGER set_timestamp;


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
-- Name: prest_comissao_config trg_update_prest_comissao_config_timestamp; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_update_prest_comissao_config_timestamp BEFORE UPDATE ON public.prest_comissao_config FOR EACH ROW EXECUTE FUNCTION public.update_prest_comissao_config_timestamp();


--
-- Name: prest_regras_parcelamento trigger_atualizar_data_regras_parcelamento; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trigger_atualizar_data_regras_parcelamento BEFORE UPDATE ON public.prest_regras_parcelamento FOR EACH ROW EXECUTE FUNCTION public.atualizar_data_atualizacao_regras_parcelamento();


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
-- Name: prest_colaboradores fk_colaborador_prest_usuario_login; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_colaboradores
    ADD CONSTRAINT fk_colaborador_prest_usuario_login FOREIGN KEY (prest_usuario_login_id) REFERENCES public.prest_usuarios(id) ON DELETE SET NULL;


--
-- Name: prest_comissao_config fk_comissao_config_categoria; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissao_config
    ADD CONSTRAINT fk_comissao_config_categoria FOREIGN KEY (categoria_id) REFERENCES public.prest_categorias(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: prest_comissao_config fk_comissao_config_colaborador; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissao_config
    ADD CONSTRAINT fk_comissao_config_colaborador FOREIGN KEY (colaborador_id) REFERENCES public.prest_colaboradores(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_comissoes fk_comissao_config_id; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissoes
    ADD CONSTRAINT fk_comissao_config_id FOREIGN KEY (comissao_config_id) REFERENCES public.prest_comissao_config(id) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: prest_comissao_config fk_comissao_config_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissao_config
    ADD CONSTRAINT fk_comissao_config_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_cupons_fiscais fk_cupons_fiscais_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cupons_fiscais
    ADD CONSTRAINT fk_cupons_fiscais_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_cupons_fiscais fk_cupons_fiscais_venda; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_cupons_fiscais
    ADD CONSTRAINT fk_cupons_fiscais_venda FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_disparo_itens fk_disparo_itens_disparo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_disparo_itens
    ADD CONSTRAINT fk_disparo_itens_disparo FOREIGN KEY (disparo_id) REFERENCES public.prest_disparos_massa(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: loja_configuracao fk_loja_config_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.loja_configuracao
    ADD CONSTRAINT fk_loja_config_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_dados_financeiros fk_prest_dados_financeiros_produto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_dados_financeiros
    ADD CONSTRAINT fk_prest_dados_financeiros_produto FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_dados_financeiros fk_prest_dados_financeiros_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_dados_financeiros
    ADD CONSTRAINT fk_prest_dados_financeiros_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_produto_cards fk_produto_cards_produto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_cards
    ADD CONSTRAINT fk_produto_cards_produto FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_produto_cards fk_produto_cards_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_cards
    ADD CONSTRAINT fk_produto_cards_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_regras_parcelamento fk_regras_parcelamento_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_regras_parcelamento
    ADD CONSTRAINT fk_regras_parcelamento_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


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
-- Name: asaas_cobrancas fk_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_cobrancas
    ADD CONSTRAINT fk_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: mercadopago_preferencias fk_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.mercadopago_preferencias
    ADD CONSTRAINT fk_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: asaas_clientes fk_usuario_cliente; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asaas_clientes
    ADD CONSTRAINT fk_usuario_cliente FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: pulse_whatsapp_config fk_whatsapp_config_empresa; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pulse_whatsapp_config
    ADD CONSTRAINT fk_whatsapp_config_empresa FOREIGN KEY (empresa_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


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
-- Name: prest_caixa prest_caixa_colaborador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa
    ADD CONSTRAINT prest_caixa_colaborador_id_fkey FOREIGN KEY (colaborador_id) REFERENCES public.prest_colaboradores(id) ON DELETE SET NULL;


--
-- Name: prest_caixa_movimentacoes prest_caixa_movimentacoes_caixa_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa_movimentacoes
    ADD CONSTRAINT prest_caixa_movimentacoes_caixa_id_fkey FOREIGN KEY (caixa_id) REFERENCES public.prest_caixa(id) ON DELETE CASCADE;


--
-- Name: prest_caixa_movimentacoes prest_caixa_movimentacoes_forma_pagamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa_movimentacoes
    ADD CONSTRAINT prest_caixa_movimentacoes_forma_pagamento_id_fkey FOREIGN KEY (forma_pagamento_id) REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL;


--
-- Name: prest_caixa_movimentacoes prest_caixa_movimentacoes_parcela_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa_movimentacoes
    ADD CONSTRAINT prest_caixa_movimentacoes_parcela_id_fkey FOREIGN KEY (parcela_id) REFERENCES public.prest_parcelas(id) ON DELETE SET NULL;


--
-- Name: prest_caixa_movimentacoes prest_caixa_movimentacoes_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa_movimentacoes
    ADD CONSTRAINT prest_caixa_movimentacoes_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON DELETE SET NULL;


--
-- Name: prest_caixa prest_caixa_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_caixa
    ADD CONSTRAINT prest_caixa_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_carteira_cobranca prest_carteira_cobranca_cliente_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_cobranca_cliente_id_fkey FOREIGN KEY (cliente_id) REFERENCES public.prest_clientes(id) ON DELETE RESTRICT;


--
-- Name: prest_carteira_cobranca prest_carteira_cobranca_cobrador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_cobranca_cobrador_id_fkey FOREIGN KEY (cobrador_id) REFERENCES public.prest_colaboradores(id) ON DELETE RESTRICT;


--
-- Name: prest_carteira_cobranca prest_carteira_cobranca_periodo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_cobranca_periodo_id_fkey FOREIGN KEY (periodo_id) REFERENCES public.prest_periodos_cobranca(id) ON DELETE CASCADE;


--
-- Name: prest_carteira_cobranca prest_carteira_cobranca_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_cobranca_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_carteira_cobranca prest_carteira_rota_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_carteira_cobranca
    ADD CONSTRAINT prest_carteira_rota_id_fkey FOREIGN KEY (rota_id) REFERENCES public.prest_rotas_cobranca(id) ON DELETE SET NULL;


--
-- Name: prest_categorias prest_categorias_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_categorias
    ADD CONSTRAINT prest_categorias_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_clientes prest_clientes_regiao_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_regiao_id_fkey FOREIGN KEY (regiao_id) REFERENCES public.prest_regioes(id) ON DELETE SET NULL;


--
-- Name: prest_clientes prest_clientes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_clientes
    ADD CONSTRAINT prest_clientes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


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
-- Name: prest_colaboradores prest_colaboradores_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_colaboradores
    ADD CONSTRAINT prest_colaboradores_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_comissoes prest_comissoes_parcela_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissoes
    ADD CONSTRAINT prest_comissoes_parcela_id_fkey FOREIGN KEY (parcela_id) REFERENCES public.prest_parcelas(id) ON DELETE CASCADE;


--
-- Name: prest_comissoes prest_comissoes_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissoes
    ADD CONSTRAINT prest_comissoes_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_comissoes prest_comissoes_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissoes
    ADD CONSTRAINT prest_comissoes_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id) ON DELETE CASCADE;


--
-- Name: prest_comissoes prest_comissoes_vendedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comissoes
    ADD CONSTRAINT prest_comissoes_vendedor_id_fkey FOREIGN KEY (colaborador_id) REFERENCES public.prest_vendedores(id) ON DELETE RESTRICT;


--
-- Name: prest_compras prest_compras_fornecedor_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_compras
    ADD CONSTRAINT prest_compras_fornecedor_fk FOREIGN KEY (fornecedor_id) REFERENCES public.prest_fornecedores(id) ON DELETE RESTRICT;


--
-- Name: prest_compras prest_compras_fornecedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_compras
    ADD CONSTRAINT prest_compras_fornecedor_id_fkey FOREIGN KEY (fornecedor_id) REFERENCES public.prest_fornecedores(id) ON DELETE RESTRICT;


--
-- Name: prest_compras prest_compras_usuario_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_compras
    ADD CONSTRAINT prest_compras_usuario_fk FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_compras prest_compras_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_compras
    ADD CONSTRAINT prest_compras_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


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
-- Name: prest_dispositivos_pagamento prest_dispositivos_pagamento_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_dispositivos_pagamento
    ADD CONSTRAINT prest_dispositivos_pagamento_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


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
-- Name: prest_fornecedores prest_fornecedores_usuario_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_fornecedores
    ADD CONSTRAINT prest_fornecedores_usuario_fk FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_fornecedores prest_fornecedores_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_fornecedores
    ADD CONSTRAINT prest_fornecedores_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


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
-- Name: prest_itens_compra prest_itens_compra_compra_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_itens_compra
    ADD CONSTRAINT prest_itens_compra_compra_fk FOREIGN KEY (compra_id) REFERENCES public.prest_compras(id) ON DELETE CASCADE;


--
-- Name: prest_itens_compra prest_itens_compra_compra_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_itens_compra
    ADD CONSTRAINT prest_itens_compra_compra_id_fkey FOREIGN KEY (compra_id) REFERENCES public.prest_compras(id) ON DELETE CASCADE;


--
-- Name: prest_itens_compra prest_itens_compra_produto_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_itens_compra
    ADD CONSTRAINT prest_itens_compra_produto_fk FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE RESTRICT;


--
-- Name: prest_itens_compra prest_itens_compra_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_itens_compra
    ADD CONSTRAINT prest_itens_compra_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE RESTRICT;


--
-- Name: prest_marketplace_config prest_marketplace_config_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_config
    ADD CONSTRAINT prest_marketplace_config_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_marketplace_pedido_item prest_marketplace_pedido_item_pedido_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido_item
    ADD CONSTRAINT prest_marketplace_pedido_item_pedido_id_fkey FOREIGN KEY (pedido_id) REFERENCES public.prest_marketplace_pedido(id) ON DELETE CASCADE;


--
-- Name: prest_marketplace_pedido_item prest_marketplace_pedido_item_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido_item
    ADD CONSTRAINT prest_marketplace_pedido_item_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id);


--
-- Name: prest_marketplace_pedido prest_marketplace_pedido_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido
    ADD CONSTRAINT prest_marketplace_pedido_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_marketplace_pedido prest_marketplace_pedido_venda_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_pedido
    ADD CONSTRAINT prest_marketplace_pedido_venda_id_fkey FOREIGN KEY (venda_id) REFERENCES public.prest_vendas(id);


--
-- Name: prest_marketplace_produto prest_marketplace_produto_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_produto
    ADD CONSTRAINT prest_marketplace_produto_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE CASCADE;


--
-- Name: prest_marketplace_sync_log prest_marketplace_sync_log_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_sync_log
    ADD CONSTRAINT prest_marketplace_sync_log_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


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
-- Name: prest_parcelas prest_parcelas_carteira_cobranca_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_carteira_cobranca_id_fkey FOREIGN KEY (carteira_cobranca_id) REFERENCES public.prest_carteira_cobranca(id) ON DELETE SET NULL;


--
-- Name: prest_parcelas prest_parcelas_cobrador_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_cobrador_id_fkey FOREIGN KEY (cobrador_id) REFERENCES public.prest_colaboradores(id) ON DELETE SET NULL;


--
-- Name: prest_parcelas prest_parcelas_forma_pagamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_parcelas
    ADD CONSTRAINT prest_parcelas_forma_pagamento_id_fkey FOREIGN KEY (forma_pagamento_id) REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL;


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
-- Name: prest_periodos_cobranca prest_periodos_cobranca_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_periodos_cobranca
    ADD CONSTRAINT prest_periodos_cobranca_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: prest_produto_fotos prest_produto_fotos_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_fotos
    ADD CONSTRAINT prest_produto_fotos_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE CASCADE;


--
-- Name: prest_produto_kit_itens prest_produto_kit_itens_kit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_kit_itens
    ADD CONSTRAINT prest_produto_kit_itens_kit_id_fkey FOREIGN KEY (kit_id) REFERENCES public.prest_produtos(id) ON DELETE CASCADE;


--
-- Name: prest_produto_kit_itens prest_produto_kit_itens_produto_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_kit_itens
    ADD CONSTRAINT prest_produto_kit_itens_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON DELETE RESTRICT;


--
-- Name: prest_produtos prest_produtos_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produtos
    ADD CONSTRAINT prest_produtos_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.prest_categorias(id) ON DELETE SET NULL;


--
-- Name: prest_produtos prest_produtos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produtos
    ADD CONSTRAINT prest_produtos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


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
-- Name: prest_vendas prest_vendas_colaborador_vendedor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendas
    ADD CONSTRAINT prest_vendas_colaborador_vendedor_id_fkey FOREIGN KEY (colaborador_vendedor_id) REFERENCES public.prest_colaboradores(id) ON DELETE SET NULL;


--
-- Name: prest_vendas prest_vendas_forma_pagamento_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendas
    ADD CONSTRAINT prest_vendas_forma_pagamento_id_fkey FOREIGN KEY (forma_pagamento_id) REFERENCES public.prest_formas_pagamento(id) ON DELETE SET NULL;


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
-- Name: prest_vendedores prest_vendedores_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_vendedores
    ADD CONSTRAINT prest_vendedores_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE RESTRICT;


--
-- Name: saas_financial_logs saas_financial_logs_order_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.saas_financial_logs
    ADD CONSTRAINT saas_financial_logs_order_id_fkey FOREIGN KEY (order_id) REFERENCES public.prest_vendas(id) ON DELETE CASCADE;


--
-- Name: saas_financial_logs saas_financial_logs_tenant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.saas_financial_logs
    ADD CONSTRAINT saas_financial_logs_tenant_id_fkey FOREIGN KEY (tenant_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


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
-- Name: sis_assinaturas sis_assinaturas_plano_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_assinaturas
    ADD CONSTRAINT sis_assinaturas_plano_id_fkey FOREIGN KEY (plano_id) REFERENCES public.sis_planos(id);


--
-- Name: sis_assinaturas sis_assinaturas_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_assinaturas
    ADD CONSTRAINT sis_assinaturas_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: sis_pagamentos sis_pagamentos_assinatura_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_pagamentos
    ADD CONSTRAINT sis_pagamentos_assinatura_id_fkey FOREIGN KEY (assinatura_id) REFERENCES public.sis_assinaturas(id) ON DELETE CASCADE;


--
-- Name: sis_pagamentos sis_pagamentos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_pagamentos
    ADD CONSTRAINT sis_pagamentos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id);


--
-- Name: sis_plano_modulos sis_plano_modulos_modulo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_plano_modulos
    ADD CONSTRAINT sis_plano_modulos_modulo_id_fkey FOREIGN KEY (modulo_id) REFERENCES public.sis_modulos(id) ON DELETE CASCADE;


--
-- Name: sis_plano_modulos sis_plano_modulos_plano_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_plano_modulos
    ADD CONSTRAINT sis_plano_modulos_plano_id_fkey FOREIGN KEY (plano_id) REFERENCES public.sis_planos(id) ON DELETE CASCADE;


--
-- Name: sis_usuario_modulos sis_usuario_modulos_modulo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_usuario_modulos
    ADD CONSTRAINT sis_usuario_modulos_modulo_id_fkey FOREIGN KEY (modulo_id) REFERENCES public.sis_modulos(id) ON DELETE CASCADE;


--
-- Name: sis_usuario_modulos sis_usuario_modulos_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sis_usuario_modulos
    ADD CONSTRAINT sis_usuario_modulos_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: many_sys_modulos_has_many_ind_dimensoes_indicadores sys_modulos_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_ind_dimensoes_indicadores
    ADD CONSTRAINT sys_modulos_fk FOREIGN KEY (id_sys_modulos) REFERENCES public.sys_modulos(id) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: many_sys_modulos_has_many_user sys_modulos_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_user
    ADD CONSTRAINT sys_modulos_fk FOREIGN KEY (id_sys_modulos) REFERENCES public.sys_modulos(id) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: many_sys_modulos_has_many_user user_fk; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.many_sys_modulos_has_many_user
    ADD CONSTRAINT user_fk FOREIGN KEY (id_user) REFERENCES public."user"(id) MATCH FULL ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict eC7xSkPEEIHWWMMORtjVmNoMJHUWLg6PyHlttZmM57w6R1ky7a9VKq5NfyhG73w

