--
-- PostgreSQL database dump
--

\restrict 1z6O15LKHiIYM9mWJGWohuW0vBo6Y1Qx3T8Hvw5EMQWvlbc8jxMXF10R0v20hwh

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

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
-- Name: prest_comanda_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_comanda_itens (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    comanda_id uuid NOT NULL,
    produto_id uuid NOT NULL,
    quantidade numeric(10,3) DEFAULT 1 NOT NULL,
    valor_unitario numeric(10,2) NOT NULL,
    observacoes text,
    destino_preparo character varying(30) DEFAULT 'cozinha'::character varying,
    status_preparo character varying(30) DEFAULT 'pendente'::character varying,
    data_pedido timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_comanda_itens OWNER TO postgres;

--
-- Name: prest_comandas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_comandas (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    mesa_id uuid,
    numero_comanda character varying(30) NOT NULL,
    cliente_nome character varying(150),
    status character varying(30) DEFAULT 'aberta'::character varying,
    data_abertura timestamp without time zone DEFAULT now(),
    data_fechamento timestamp without time zone
);


ALTER TABLE public.prest_comandas OWNER TO postgres;

--
-- Name: prest_disparo_itens; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_disparo_itens (
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

CREATE TABLE public.prest_disparos_massa (
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
-- Name: prest_encarte_produtos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_encarte_produtos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    encarte_id uuid NOT NULL,
    produto_id character varying(36) NOT NULL,
    preco_oferta numeric(10,2) DEFAULT NULL::numeric,
    ordem integer DEFAULT 0 NOT NULL,
    destaque boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP,
    tag_promocional character varying(50)
);


ALTER TABLE public.prest_encarte_produtos OWNER TO postgres;

--
-- Name: prest_encartes; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_encartes (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id character varying(36) NOT NULL,
    titulo character varying(255) NOT NULL,
    subtitulo character varying(255) DEFAULT NULL::character varying,
    token_publico character varying(64) NOT NULL,
    estilo_layout character varying(50) DEFAULT 'flipsnack_supermarket'::character varying NOT NULL,
    produtos_por_pagina integer DEFAULT 6 NOT NULL,
    cor_tema character varying(50) DEFAULT 'red_gold'::character varying NOT NULL,
    status character varying(20) DEFAULT 'ativo'::character varying NOT NULL,
    visualizacoes_count integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_encartes OWNER TO postgres;

--
-- Name: prest_marketplace_categoria_map; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_marketplace_categoria_map (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    categoria_id uuid NOT NULL,
    marketplace character varying(50) NOT NULL,
    marketplace_categoria_id character varying(100) CONSTRAINT prest_marketplace_categoria_m_marketplace_categoria_id_not_null NOT NULL,
    marketplace_categoria_nome character varying(255),
    regras_atributos jsonb DEFAULT '{}'::jsonb,
    data_criacao timestamp with time zone DEFAULT now(),
    data_atualizacao timestamp with time zone DEFAULT now()
);


ALTER TABLE public.prest_marketplace_categoria_map OWNER TO postgres;

--
-- Name: TABLE prest_marketplace_categoria_map; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.prest_marketplace_categoria_map IS 'Mapeamento de categorias do ERP para a taxonomia de cada marketplace';


--
-- Name: prest_mesas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_mesas (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    numero_mesa character varying(20) NOT NULL,
    nome_identificador character varying(100),
    status character varying(30) DEFAULT 'livre'::character varying,
    lugares integer DEFAULT 4,
    data_criacao timestamp without time zone DEFAULT now(),
    data_atualizacao timestamp without time zone DEFAULT now()
);


ALTER TABLE public.prest_mesas OWNER TO postgres;

--
-- Name: prest_produto_cards; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_produto_cards (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    produto_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    formato character varying(20) NOT NULL,
    card_path character varying(500) NOT NULL,
    card_url character varying(500),
    metadata jsonb,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT prest_produto_cards_formato_check CHECK (((formato)::text = ANY (ARRAY[('feed'::character varying)::text, ('stories'::character varying)::text])))
);


ALTER TABLE public.prest_produto_cards OWNER TO postgres;

--
-- Name: prest_produto_opcionais; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_produto_opcionais (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    produto_id uuid NOT NULL,
    nome character varying(100) NOT NULL,
    valor_adicional numeric(10,2) DEFAULT 0.00,
    ativo boolean DEFAULT true
);


ALTER TABLE public.prest_produto_opcionais OWNER TO postgres;

--
-- Name: prest_produto_videos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_produto_videos (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    produto_id uuid NOT NULL,
    usuario_id uuid NOT NULL,
    duracao integer DEFAULT 15 NOT NULL,
    formato character varying(20) DEFAULT 'stories'::character varying NOT NULL,
    status character varying(20) DEFAULT 'pendente'::character varying NOT NULL,
    video_path character varying(500),
    video_url character varying(500),
    erro_mensagem text,
    metadata jsonb,
    data_criacao timestamp with time zone DEFAULT now() NOT NULL,
    data_atualizacao timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_produto_videos OWNER TO postgres;

--
-- Name: prest_saas_config_global; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_saas_config_global (
    id integer NOT NULL,
    chave character varying(100) NOT NULL,
    valor text,
    descricao character varying(255),
    data_atualizacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_saas_config_global OWNER TO postgres;

--
-- Name: prest_saas_config_global_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.prest_saas_config_global_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prest_saas_config_global_id_seq OWNER TO postgres;

--
-- Name: prest_saas_config_global_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.prest_saas_config_global_id_seq OWNED BY public.prest_saas_config_global.id;


--
-- Name: prest_saas_faturas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_saas_faturas (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    mes_referencia character varying(7) NOT NULL,
    data_fechamento date NOT NULL,
    data_vencimento date NOT NULL,
    gmv_marketplace numeric(12,2) DEFAULT 0.00 NOT NULL,
    gmv_catalogo numeric(12,2) DEFAULT 0.00 NOT NULL,
    total_pedidos_marketplace integer DEFAULT 0 NOT NULL,
    total_pedidos_catalogo integer DEFAULT 0 NOT NULL,
    valor_mensalidade numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_comissao_marketplace numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_comissao_catalogo numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_pedidos_excedentes numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_descontos numeric(10,2) DEFAULT 0.00 NOT NULL,
    valor_total numeric(10,2) DEFAULT 0.00 NOT NULL,
    status character varying(30) DEFAULT 'pendente'::character varying NOT NULL,
    data_pagamento timestamp without time zone,
    metodo_pagamento character varying(50),
    qr_code_pix text,
    codigo_pix text,
    link_pagamento text,
    transacao_gateway_id character varying(150),
    detalhes_json jsonb DEFAULT '{}'::jsonb,
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_saas_faturas OWNER TO postgres;

--
-- Name: prest_saas_loja_config; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_saas_loja_config (
    id integer NOT NULL,
    usuario_id uuid NOT NULL,
    plano_id integer,
    dia_vencimento integer DEFAULT 10 NOT NULL,
    percentual_custom_catalogo numeric(5,2),
    percentual_custom_marketplace numeric(5,2),
    valor_custom_mensalidade numeric(10,2),
    status_cobranca character varying(30) DEFAULT 'adimplente'::character varying NOT NULL,
    dias_carencia_bloqueio integer DEFAULT 5 NOT NULL,
    observacoes text,
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_saas_loja_config OWNER TO postgres;

--
-- Name: prest_saas_loja_config_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.prest_saas_loja_config_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prest_saas_loja_config_id_seq OWNER TO postgres;

--
-- Name: prest_saas_loja_config_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.prest_saas_loja_config_id_seq OWNED BY public.prest_saas_loja_config.id;


--
-- Name: prest_saas_planos; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_saas_planos (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    descricao text,
    valor_mensalidade numeric(10,2) DEFAULT 0.00 NOT NULL,
    percentual_comissao_catalogo numeric(5,2) DEFAULT 2.50 NOT NULL,
    percentual_comissao_marketplace numeric(5,2) DEFAULT 1.00 NOT NULL,
    limite_pedidos_inclusos integer DEFAULT 300 NOT NULL,
    valor_pedido_excedente numeric(10,2) DEFAULT 0.50 NOT NULL,
    ativo boolean DEFAULT true NOT NULL,
    destaque boolean DEFAULT false NOT NULL,
    data_criacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.prest_saas_planos OWNER TO postgres;

--
-- Name: prest_saas_planos_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.prest_saas_planos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.prest_saas_planos_id_seq OWNER TO postgres;

--
-- Name: prest_saas_planos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.prest_saas_planos_id_seq OWNED BY public.prest_saas_planos.id;


--
-- Name: prest_social_accounts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_social_accounts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    tenant_id uuid NOT NULL,
    facebook_page_id character varying(255),
    instagram_business_account_id character varying(255),
    page_name character varying(255) NOT NULL,
    access_token text NOT NULL,
    token_expires_at timestamp with time zone,
    status character varying(50) DEFAULT 'ACTIVE'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_social_accounts OWNER TO postgres;

--
-- Name: prest_social_posts; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_social_posts (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    tenant_id uuid NOT NULL,
    social_account_id uuid NOT NULL,
    platform character varying(20) DEFAULT 'INSTAGRAM'::character varying NOT NULL,
    media_type character varying(20) NOT NULL,
    media_url text NOT NULL,
    caption text,
    creation_id character varying(255),
    published_media_id character varying(255),
    status character varying(50) DEFAULT 'PENDING'::character varying NOT NULL,
    error_payload jsonb,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.prest_social_posts OWNER TO postgres;

--
-- Name: prest_trilhas_sonoras; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.prest_trilhas_sonoras (
    id uuid DEFAULT gen_random_uuid() NOT NULL,
    usuario_id uuid NOT NULL,
    titulo character varying(255) NOT NULL,
    descricao text,
    arquivo_nome character varying(255) NOT NULL,
    arquivo_path character varying(500) NOT NULL,
    formato character varying(10) DEFAULT 'mp3'::character varying NOT NULL,
    tamanho_bytes bigint DEFAULT 0,
    ativo boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    tipo character varying(30) DEFAULT 'musica'::character varying NOT NULL
);


ALTER TABLE public.prest_trilhas_sonoras OWNER TO postgres;

--
-- Name: queue; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.queue (
    id integer NOT NULL,
    channel character varying(255) NOT NULL,
    job bytea NOT NULL,
    pushed_at integer NOT NULL,
    ttr integer NOT NULL,
    delay integer DEFAULT 0 NOT NULL,
    priority integer DEFAULT 1024 NOT NULL,
    reserved_at integer,
    attempt integer,
    done_at integer
);


ALTER TABLE public.queue OWNER TO postgres;

--
-- Name: queue_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.queue_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.queue_id_seq OWNER TO postgres;

--
-- Name: queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.queue_id_seq OWNED BY public.queue.id;


--
-- Name: prest_saas_config_global id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_config_global ALTER COLUMN id SET DEFAULT nextval('public.prest_saas_config_global_id_seq'::regclass);


--
-- Name: prest_saas_loja_config id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_loja_config ALTER COLUMN id SET DEFAULT nextval('public.prest_saas_loja_config_id_seq'::regclass);


--
-- Name: prest_saas_planos id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_planos ALTER COLUMN id SET DEFAULT nextval('public.prest_saas_planos_id_seq'::regclass);


--
-- Name: queue id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.queue ALTER COLUMN id SET DEFAULT nextval('public.queue_id_seq'::regclass);


--
-- Name: prest_comanda_itens prest_comanda_itens_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comanda_itens
    ADD CONSTRAINT prest_comanda_itens_pkey PRIMARY KEY (id);


--
-- Name: prest_comandas prest_comandas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_comandas
    ADD CONSTRAINT prest_comandas_pkey PRIMARY KEY (id);


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
-- Name: prest_encarte_produtos prest_encarte_produtos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_encarte_produtos
    ADD CONSTRAINT prest_encarte_produtos_pkey PRIMARY KEY (id);


--
-- Name: prest_encartes prest_encartes_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_encartes
    ADD CONSTRAINT prest_encartes_pkey PRIMARY KEY (id);


--
-- Name: prest_marketplace_categoria_map prest_marketplace_categoria_m_usuario_id_categoria_id_marke_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_categoria_map
    ADD CONSTRAINT prest_marketplace_categoria_m_usuario_id_categoria_id_marke_key UNIQUE (usuario_id, categoria_id, marketplace);


--
-- Name: prest_marketplace_categoria_map prest_marketplace_categoria_map_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_categoria_map
    ADD CONSTRAINT prest_marketplace_categoria_map_pkey PRIMARY KEY (id);


--
-- Name: prest_mesas prest_mesas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_mesas
    ADD CONSTRAINT prest_mesas_pkey PRIMARY KEY (id);


--
-- Name: prest_produto_cards prest_produto_cards_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_cards
    ADD CONSTRAINT prest_produto_cards_pkey PRIMARY KEY (id);


--
-- Name: prest_produto_opcionais prest_produto_opcionais_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_opcionais
    ADD CONSTRAINT prest_produto_opcionais_pkey PRIMARY KEY (id);


--
-- Name: prest_produto_videos prest_produto_videos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_videos
    ADD CONSTRAINT prest_produto_videos_pkey PRIMARY KEY (id);


--
-- Name: prest_saas_config_global prest_saas_config_global_chave_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_config_global
    ADD CONSTRAINT prest_saas_config_global_chave_key UNIQUE (chave);


--
-- Name: prest_saas_config_global prest_saas_config_global_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_config_global
    ADD CONSTRAINT prest_saas_config_global_pkey PRIMARY KEY (id);


--
-- Name: prest_saas_faturas prest_saas_faturas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_faturas
    ADD CONSTRAINT prest_saas_faturas_pkey PRIMARY KEY (id);


--
-- Name: prest_saas_loja_config prest_saas_loja_config_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_loja_config
    ADD CONSTRAINT prest_saas_loja_config_pkey PRIMARY KEY (id);


--
-- Name: prest_saas_loja_config prest_saas_loja_config_usuario_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_loja_config
    ADD CONSTRAINT prest_saas_loja_config_usuario_id_key UNIQUE (usuario_id);


--
-- Name: prest_saas_planos prest_saas_planos_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_planos
    ADD CONSTRAINT prest_saas_planos_pkey PRIMARY KEY (id);


--
-- Name: prest_social_accounts prest_social_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_social_accounts
    ADD CONSTRAINT prest_social_accounts_pkey PRIMARY KEY (id);


--
-- Name: prest_social_posts prest_social_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_social_posts
    ADD CONSTRAINT prest_social_posts_pkey PRIMARY KEY (id);


--
-- Name: prest_trilhas_sonoras prest_trilhas_sonoras_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_trilhas_sonoras
    ADD CONSTRAINT prest_trilhas_sonoras_pkey PRIMARY KEY (id);


--
-- Name: queue queue_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.queue
    ADD CONSTRAINT queue_pkey PRIMARY KEY (id);


--
-- Name: idx_comanda_itens_comanda_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comanda_itens_comanda_id ON public.prest_comanda_itens USING btree (comanda_id);


--
-- Name: idx_comanda_itens_destino; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comanda_itens_destino ON public.prest_comanda_itens USING btree (destino_preparo, status_preparo);


--
-- Name: idx_comandas_mesa_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comandas_mesa_id ON public.prest_comandas USING btree (mesa_id);


--
-- Name: idx_comandas_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comandas_status ON public.prest_comandas USING btree (usuario_id, status);


--
-- Name: idx_comandas_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_comandas_usuario_id ON public.prest_comandas USING btree (usuario_id);


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
-- Name: idx_encarte_produtos_encarte; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_encarte_produtos_encarte ON public.prest_encarte_produtos USING btree (encarte_id);


--
-- Name: idx_encarte_produtos_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_encarte_produtos_produto ON public.prest_encarte_produtos USING btree (produto_id);


--
-- Name: idx_encartes_token_publico; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX idx_encartes_token_publico ON public.prest_encartes USING btree (token_publico);


--
-- Name: idx_encartes_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_encartes_usuario ON public.prest_encartes USING btree (usuario_id);


--
-- Name: idx_marketplace_cat_map_cat; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_cat_map_cat ON public.prest_marketplace_categoria_map USING btree (categoria_id);


--
-- Name: idx_marketplace_cat_map_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_marketplace_cat_map_usuario ON public.prest_marketplace_categoria_map USING btree (usuario_id);


--
-- Name: idx_mesas_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mesas_status ON public.prest_mesas USING btree (usuario_id, status);


--
-- Name: idx_mesas_usuario_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mesas_usuario_id ON public.prest_mesas USING btree (usuario_id);


--
-- Name: idx_mktp_cat_map_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_mktp_cat_map_user ON public.prest_marketplace_categoria_map USING btree (usuario_id, marketplace);


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
-- Name: idx_prest_produto_videos_produto; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_produto_videos_produto ON public.prest_produto_videos USING btree (produto_id);


--
-- Name: idx_prest_produto_videos_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_produto_videos_status ON public.prest_produto_videos USING btree (status);


--
-- Name: idx_prest_produto_videos_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_produto_videos_usuario ON public.prest_produto_videos USING btree (usuario_id);


--
-- Name: idx_prest_trilhas_sonoras_tipo; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_prest_trilhas_sonoras_tipo ON public.prest_trilhas_sonoras USING btree (tipo);


--
-- Name: idx_produto_opcionais_produto_id; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_produto_opcionais_produto_id ON public.prest_produto_opcionais USING btree (produto_id);


--
-- Name: idx_queue_channel; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_queue_channel ON public.queue USING btree (channel);


--
-- Name: idx_queue_priority; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_queue_priority ON public.queue USING btree (priority);


--
-- Name: idx_queue_reserved_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_queue_reserved_at ON public.queue USING btree (reserved_at);


--
-- Name: idx_saas_faturas_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_faturas_status ON public.prest_saas_faturas USING btree (status);


--
-- Name: idx_saas_faturas_usuario_mes; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_faturas_usuario_mes ON public.prest_saas_faturas USING btree (usuario_id, mes_referencia);


--
-- Name: idx_saas_faturas_vencimento; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_faturas_vencimento ON public.prest_saas_faturas USING btree (data_vencimento);


--
-- Name: idx_saas_loja_config_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_loja_config_status ON public.prest_saas_loja_config USING btree (status_cobranca);


--
-- Name: idx_saas_loja_config_usuario; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_saas_loja_config_usuario ON public.prest_saas_loja_config USING btree (usuario_id);


--
-- Name: idx_social_accounts_fb_page; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_accounts_fb_page ON public.prest_social_accounts USING btree (facebook_page_id);


--
-- Name: idx_social_accounts_ig_acc; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_accounts_ig_acc ON public.prest_social_accounts USING btree (instagram_business_account_id);


--
-- Name: idx_social_accounts_tenant; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_accounts_tenant ON public.prest_social_accounts USING btree (tenant_id);


--
-- Name: idx_social_posts_account; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_posts_account ON public.prest_social_posts USING btree (social_account_id);


--
-- Name: idx_social_posts_creation; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_posts_creation ON public.prest_social_posts USING btree (creation_id);


--
-- Name: idx_social_posts_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_posts_status ON public.prest_social_posts USING btree (status);


--
-- Name: idx_social_posts_tenant; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_social_posts_tenant ON public.prest_social_posts USING btree (tenant_id);


--
-- Name: prest_social_accounts trg_social_accounts_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_social_accounts_updated_at BEFORE UPDATE ON public.prest_social_accounts FOR EACH ROW EXECUTE FUNCTION public.update_social_tables_updated_at();


--
-- Name: prest_social_posts trg_social_posts_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_social_posts_updated_at BEFORE UPDATE ON public.prest_social_posts FOR EACH ROW EXECUTE FUNCTION public.update_social_tables_updated_at();


--
-- Name: prest_disparo_itens fk_disparo_itens_disparo; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_disparo_itens
    ADD CONSTRAINT fk_disparo_itens_disparo FOREIGN KEY (disparo_id) REFERENCES public.prest_disparos_massa(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_encarte_produtos fk_encarte_produtos_encarte; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_encarte_produtos
    ADD CONSTRAINT fk_encarte_produtos_encarte FOREIGN KEY (encarte_id) REFERENCES public.prest_encartes(id) ON UPDATE CASCADE ON DELETE CASCADE;


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
-- Name: prest_produto_videos fk_produto_videos_produto; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_videos
    ADD CONSTRAINT fk_produto_videos_produto FOREIGN KEY (produto_id) REFERENCES public.prest_produtos(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_produto_videos fk_produto_videos_usuario; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_produto_videos
    ADD CONSTRAINT fk_produto_videos_usuario FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_social_accounts fk_social_accounts_tenant; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_social_accounts
    ADD CONSTRAINT fk_social_accounts_tenant FOREIGN KEY (tenant_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_social_posts fk_social_posts_account; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_social_posts
    ADD CONSTRAINT fk_social_posts_account FOREIGN KEY (social_account_id) REFERENCES public.prest_social_accounts(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_social_posts fk_social_posts_tenant; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_social_posts
    ADD CONSTRAINT fk_social_posts_tenant FOREIGN KEY (tenant_id) REFERENCES public.prest_usuarios(id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: prest_marketplace_categoria_map prest_marketplace_categoria_map_categoria_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_categoria_map
    ADD CONSTRAINT prest_marketplace_categoria_map_categoria_id_fkey FOREIGN KEY (categoria_id) REFERENCES public.prest_categorias(id) ON DELETE CASCADE;


--
-- Name: prest_marketplace_categoria_map prest_marketplace_categoria_map_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_marketplace_categoria_map
    ADD CONSTRAINT prest_marketplace_categoria_map_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_saas_faturas prest_saas_faturas_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_faturas
    ADD CONSTRAINT prest_saas_faturas_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- Name: prest_saas_loja_config prest_saas_loja_config_plano_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_loja_config
    ADD CONSTRAINT prest_saas_loja_config_plano_id_fkey FOREIGN KEY (plano_id) REFERENCES public.prest_saas_planos(id) ON DELETE SET NULL;


--
-- Name: prest_saas_loja_config prest_saas_loja_config_usuario_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.prest_saas_loja_config
    ADD CONSTRAINT prest_saas_loja_config_usuario_id_fkey FOREIGN KEY (usuario_id) REFERENCES public.prest_usuarios(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 1z6O15LKHiIYM9mWJGWohuW0vBo6Y1Qx3T8Hvw5EMQWvlbc8jxMXF10R0v20hwh


-- ==============================================================================
-- COLUNAS FALTANTES EM TABELAS EXISTENTES
-- ==============================================================================

-- 1. pulse_whatsapp_config (Anti-ban, proxies e limites)
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS proxy_host VARCHAR(255);
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS proxy_user VARCHAR(255);
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS proxy_pass VARCHAR(255);
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS lote_tamanho INTEGER NOT NULL DEFAULT 15;
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS lote_pausa_segundos INTEGER NOT NULL DEFAULT 120;
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS limite_diario_mensagens INTEGER NOT NULL DEFAULT 150;
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS mensagens_enviadas_hoje INTEGER NOT NULL DEFAULT 0;
ALTER TABLE pulse_whatsapp_config ADD COLUMN IF NOT EXISTS data_contador_diario DATE;

-- 2. prest_produtos (Fiscal, logística e estoque)
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS peso_bruto DECIMAL(10,3);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS peso_liquido DECIMAL(10,3);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS altura_cm DECIMAL(10,2);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS largura_cm DECIMAL(10,2);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS comprimento_cm DECIMAL(10,2);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS ncm VARCHAR(10);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS cest VARCHAR(10);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS ean_gtin VARCHAR(20);
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS origem_mercadoria CHAR(1) DEFAULT '0';
ALTER TABLE prest_produtos ADD COLUMN IF NOT EXISTS permite_estoque_negativo BOOLEAN DEFAULT false;

-- 3. prest_marketplace_config (Regras de markup e contas)
ALTER TABLE prest_marketplace_config ADD COLUMN IF NOT EXISTS seller_id_externo VARCHAR(100);
ALTER TABLE prest_marketplace_config ADD COLUMN IF NOT EXISTS apelido_conta VARCHAR(100);
ALTER TABLE prest_marketplace_config ADD COLUMN IF NOT EXISTS dados_adicionais JSONB;
ALTER TABLE prest_marketplace_config ADD COLUMN IF NOT EXISTS markup_percentual DECIMAL(10,4);
ALTER TABLE prest_marketplace_config ADD COLUMN IF NOT EXISTS markup_valor_fixo DECIMAL(10,2);
ALTER TABLE prest_marketplace_config ADD COLUMN IF NOT EXISTS arredondar_centavos_99 BOOLEAN DEFAULT false;

-- 4. prest_marketplace_produto (Tenant e variações)
ALTER TABLE prest_marketplace_produto ADD COLUMN IF NOT EXISTS usuario_id UUID;
ALTER TABLE prest_marketplace_produto ADD COLUMN IF NOT EXISTS marketplace_variacao_id VARCHAR(100);

-- 5. loja_configuracao (Armazenamento de mídias)
ALTER TABLE loja_configuracao ADD COLUMN IF NOT EXISTS limite_armazenamento_videos_mb INTEGER DEFAULT 500;
ALTER TABLE loja_configuracao ADD COLUMN IF NOT EXISTS limite_armazenamento_cards_mb INTEGER DEFAULT 200;

