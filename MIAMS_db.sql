--
-- PostgreSQL database dump
--

\restrict zqK8BIek9nm7uN39KbtcpSue5eYB4XBivNTuwcrotVTszfCEyz1xbMVF5lmeGTx

-- Dumped from database version 18.1
-- Dumped by pg_dump version 18.1

-- Started on 2026-01-24 21:47:56

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
-- TOC entry 240 (class 1259 OID 16667)
-- Name: asset_documents; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_documents (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint,
    uploaded_by integer,
    filename text NOT NULL,
    mimetype text,
    object_path text NOT NULL,
    size_bytes bigint,
    uploaded_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.asset_documents OWNER TO postgres;

--
-- TOC entry 239 (class 1259 OID 16666)
-- Name: asset_documents_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_documents_id_seq OWNER TO postgres;

--
-- TOC entry 5201 (class 0 OID 0)
-- Dependencies: 239
-- Name: asset_documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_documents_id_seq OWNED BY public.asset_documents.id;


--
-- TOC entry 254 (class 1259 OID 25041)
-- Name: asset_images; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_images (
    id integer NOT NULL,
    asset_id bigint NOT NULL,
    institution_id integer NOT NULL,
    file_name character varying(255) NOT NULL,
    mime_type character varying(50) NOT NULL,
    file_size bigint NOT NULL,
    image_data bytea NOT NULL,
    is_primary boolean DEFAULT false,
    uploaded_by integer,
    uploaded_at timestamp without time zone DEFAULT now()
);


ALTER TABLE public.asset_images OWNER TO postgres;

--
-- TOC entry 5202 (class 0 OID 0)
-- Dependencies: 254
-- Name: TABLE asset_images; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.asset_images IS 'Stores asset images directly in PostgreSQL using BYTEA';


--
-- TOC entry 253 (class 1259 OID 25040)
-- Name: asset_images_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_images_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_images_id_seq OWNER TO postgres;

--
-- TOC entry 5203 (class 0 OID 0)
-- Dependencies: 253
-- Name: asset_images_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_images_id_seq OWNED BY public.asset_images.id;


--
-- TOC entry 244 (class 1259 OID 16726)
-- Name: asset_risk_scores; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_risk_scores (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint NOT NULL,
    risk_score numeric(5,4),
    risk_level text,
    predicted_failure_date date,
    model_version text,
    predicted_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.asset_risk_scores OWNER TO postgres;

--
-- TOC entry 243 (class 1259 OID 16725)
-- Name: asset_risk_scores_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_risk_scores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_risk_scores_id_seq OWNER TO postgres;

--
-- TOC entry 5204 (class 0 OID 0)
-- Dependencies: 243
-- Name: asset_risk_scores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_risk_scores_id_seq OWNED BY public.asset_risk_scores.id;


--
-- TOC entry 234 (class 1259 OID 16549)
-- Name: asset_tags; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_tags (
    id integer NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint,
    tag_code text NOT NULL,
    tag_type text DEFAULT 'barcode'::text,
    issued_at timestamp with time zone DEFAULT now(),
    active boolean DEFAULT true
);


ALTER TABLE public.asset_tags OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 16548)
-- Name: asset_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_tags_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_tags_id_seq OWNER TO postgres;

--
-- TOC entry 5205 (class 0 OID 0)
-- Dependencies: 233
-- Name: asset_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_tags_id_seq OWNED BY public.asset_tags.id;


--
-- TOC entry 230 (class 1259 OID 16490)
-- Name: asset_types; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.asset_types (
    id integer NOT NULL,
    institution_id integer,
    name text NOT NULL,
    description text
);


ALTER TABLE public.asset_types OWNER TO postgres;

--
-- TOC entry 229 (class 1259 OID 16489)
-- Name: asset_types_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.asset_types_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.asset_types_id_seq OWNER TO postgres;

--
-- TOC entry 5206 (class 0 OID 0)
-- Dependencies: 229
-- Name: asset_types_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.asset_types_id_seq OWNED BY public.asset_types.id;


--
-- TOC entry 232 (class 1259 OID 16507)
-- Name: assets; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.assets (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    department_id integer,
    asset_type_id integer,
    asset_code text NOT NULL,
    serial_number text,
    name text NOT NULL,
    description text,
    acquisition_date date,
    acquisition_cost numeric(12,2),
    condition text DEFAULT 'good'::text,
    status text DEFAULT 'available'::text,
    current_holder_id integer,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    date_retired timestamp with time zone,
    retirement_reason text,
    location_id integer
);


ALTER TABLE public.assets OWNER TO postgres;

--
-- TOC entry 5207 (class 0 OID 0)
-- Dependencies: 232
-- Name: COLUMN assets.date_retired; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.assets.date_retired IS 'Timestamp when the asset was retired';


--
-- TOC entry 5208 (class 0 OID 0)
-- Dependencies: 232
-- Name: COLUMN assets.retirement_reason; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.assets.retirement_reason IS 'Reason why the asset was retired (required when retiring)';


--
-- TOC entry 231 (class 1259 OID 16506)
-- Name: assets_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.assets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.assets_id_seq OWNER TO postgres;

--
-- TOC entry 5209 (class 0 OID 0)
-- Dependencies: 231
-- Name: assets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.assets_id_seq OWNED BY public.assets.id;


--
-- TOC entry 246 (class 1259 OID 16750)
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    institution_id integer,
    user_id integer,
    entity_type text NOT NULL,
    entity_id bigint,
    action text NOT NULL,
    old_values jsonb,
    new_values jsonb,
    details jsonb,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.audit_logs OWNER TO postgres;

--
-- TOC entry 245 (class 1259 OID 16749)
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.audit_logs_id_seq OWNER TO postgres;

--
-- TOC entry 5210 (class 0 OID 0)
-- Dependencies: 245
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- TOC entry 250 (class 1259 OID 16802)
-- Name: checkout_requests; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.checkout_requests (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint NOT NULL,
    requested_by integer NOT NULL,
    approved_by integer,
    status text DEFAULT 'pending'::text,
    reason text,
    requested_at timestamp with time zone DEFAULT now(),
    decided_at timestamp with time zone
);


ALTER TABLE public.checkout_requests OWNER TO postgres;

--
-- TOC entry 249 (class 1259 OID 16801)
-- Name: checkout_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.checkout_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.checkout_requests_id_seq OWNER TO postgres;

--
-- TOC entry 5211 (class 0 OID 0)
-- Dependencies: 249
-- Name: checkout_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.checkout_requests_id_seq OWNED BY public.checkout_requests.id;


--
-- TOC entry 222 (class 1259 OID 16407)
-- Name: departments; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.departments (
    id integer NOT NULL,
    institution_id integer NOT NULL,
    name text NOT NULL,
    code text,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.departments OWNER TO postgres;

--
-- TOC entry 221 (class 1259 OID 16406)
-- Name: departments_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.departments_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.departments_id_seq OWNER TO postgres;

--
-- TOC entry 5212 (class 0 OID 0)
-- Dependencies: 221
-- Name: departments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.departments_id_seq OWNED BY public.departments.id;


--
-- TOC entry 220 (class 1259 OID 16390)
-- Name: institutions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.institutions (
    id integer NOT NULL,
    name text NOT NULL,
    code text,
    address text,
    contact_email text,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    is_active boolean DEFAULT true NOT NULL,
    domain text,
    phone_number text,
    institution_type text,
    CONSTRAINT check_institution_type CHECK ((institution_type = ANY (ARRAY['University'::text, 'College'::text, 'School'::text, 'Research Center'::text, 'Training Institute'::text, 'Government Agency'::text, 'Other'::text])))
);


ALTER TABLE public.institutions OWNER TO postgres;

--
-- TOC entry 5213 (class 0 OID 0)
-- Dependencies: 220
-- Name: COLUMN institutions.is_active; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.institutions.is_active IS 'Indicates if the institution is active and accessible. When false, users cannot access the institution.';


--
-- TOC entry 5214 (class 0 OID 0)
-- Dependencies: 220
-- Name: COLUMN institutions.domain; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.institutions.domain IS 'Email domain for the institution (e.g., dsp.ac.tz, nuni.ac.ke). Used for automatic institution identification during login.';


--
-- TOC entry 5215 (class 0 OID 0)
-- Dependencies: 220
-- Name: COLUMN institutions.phone_number; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.institutions.phone_number IS 'Primary phone contact for the institution';


--
-- TOC entry 5216 (class 0 OID 0)
-- Dependencies: 220
-- Name: COLUMN institutions.institution_type; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.institutions.institution_type IS 'Type of institution (e.g., University, College, School, Research Center)';


--
-- TOC entry 219 (class 1259 OID 16389)
-- Name: institutions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.institutions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.institutions_id_seq OWNER TO postgres;

--
-- TOC entry 5217 (class 0 OID 0)
-- Dependencies: 219
-- Name: institutions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.institutions_id_seq OWNED BY public.institutions.id;


--
-- TOC entry 252 (class 1259 OID 24994)
-- Name: locations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.locations (
    id integer NOT NULL,
    institution_id integer NOT NULL,
    name text NOT NULL,
    building text,
    floor text,
    room text,
    description text,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.locations OWNER TO postgres;

--
-- TOC entry 251 (class 1259 OID 24993)
-- Name: locations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.locations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.locations_id_seq OWNER TO postgres;

--
-- TOC entry 5218 (class 0 OID 0)
-- Dependencies: 251
-- Name: locations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.locations_id_seq OWNED BY public.locations.id;


--
-- TOC entry 238 (class 1259 OID 16629)
-- Name: maintenance_records; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.maintenance_records (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint NOT NULL,
    reported_by integer,
    assigned_to integer,
    maintenance_type text,
    description text,
    status text DEFAULT 'open'::text,
    estimated_cost numeric(12,2) DEFAULT 0,
    start_date timestamp with time zone,
    end_date timestamp with time zone,
    created_at timestamp with time zone DEFAULT now(),
    updated_at timestamp with time zone DEFAULT now(),
    actual_completion_date timestamp with time zone,
    actual_cost numeric(12,2) DEFAULT 0,
    completion_notes text,
    closed_by integer,
    closed_at timestamp with time zone
);


ALTER TABLE public.maintenance_records OWNER TO postgres;

--
-- TOC entry 5219 (class 0 OID 0)
-- Dependencies: 238
-- Name: COLUMN maintenance_records.estimated_cost; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.maintenance_records.estimated_cost IS 'Estimated cost for maintenance (in KSH)';


--
-- TOC entry 5220 (class 0 OID 0)
-- Dependencies: 238
-- Name: COLUMN maintenance_records.actual_completion_date; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.maintenance_records.actual_completion_date IS 'The actual date when maintenance was completed';


--
-- TOC entry 5221 (class 0 OID 0)
-- Dependencies: 238
-- Name: COLUMN maintenance_records.actual_cost; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.maintenance_records.actual_cost IS 'The actual cost incurred during maintenance (in KSH)';


--
-- TOC entry 5222 (class 0 OID 0)
-- Dependencies: 238
-- Name: COLUMN maintenance_records.completion_notes; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.maintenance_records.completion_notes IS 'Notes or comments when closing the maintenance record';


--
-- TOC entry 5223 (class 0 OID 0)
-- Dependencies: 238
-- Name: COLUMN maintenance_records.closed_by; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.maintenance_records.closed_by IS 'User who closed the maintenance record';


--
-- TOC entry 5224 (class 0 OID 0)
-- Dependencies: 238
-- Name: COLUMN maintenance_records.closed_at; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.maintenance_records.closed_at IS 'Timestamp when the maintenance record was closed';


--
-- TOC entry 237 (class 1259 OID 16628)
-- Name: maintenance_records_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.maintenance_records_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.maintenance_records_id_seq OWNER TO postgres;

--
-- TOC entry 5225 (class 0 OID 0)
-- Dependencies: 237
-- Name: maintenance_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.maintenance_records_id_seq OWNED BY public.maintenance_records.id;


--
-- TOC entry 242 (class 1259 OID 16697)
-- Name: predictive_features; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.predictive_features (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint NOT NULL,
    feature_date date NOT NULL,
    usage_count integer DEFAULT 0,
    repairs_last_90d integer DEFAULT 0,
    avg_time_between_repairs numeric(8,2),
    last_maintenance_date date,
    asset_age_months integer,
    avg_maintenance_cost numeric(10,2),
    additional_json jsonb,
    created_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.predictive_features OWNER TO postgres;

--
-- TOC entry 241 (class 1259 OID 16696)
-- Name: predictive_features_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.predictive_features_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.predictive_features_id_seq OWNER TO postgres;

--
-- TOC entry 5226 (class 0 OID 0)
-- Dependencies: 241
-- Name: predictive_features_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.predictive_features_id_seq OWNED BY public.predictive_features.id;


--
-- TOC entry 224 (class 1259 OID 16426)
-- Name: roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.roles (
    id integer NOT NULL,
    name text NOT NULL,
    description text
);


ALTER TABLE public.roles OWNER TO postgres;

--
-- TOC entry 223 (class 1259 OID 16425)
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.roles_id_seq OWNER TO postgres;

--
-- TOC entry 5227 (class 0 OID 0)
-- Dependencies: 223
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- TOC entry 248 (class 1259 OID 16776)
-- Name: system_settings; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.system_settings (
    id integer NOT NULL,
    institution_id integer NOT NULL,
    key text NOT NULL,
    value jsonb
);


ALTER TABLE public.system_settings OWNER TO postgres;

--
-- TOC entry 247 (class 1259 OID 16775)
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.system_settings_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.system_settings_id_seq OWNER TO postgres;

--
-- TOC entry 5228 (class 0 OID 0)
-- Dependencies: 247
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- TOC entry 236 (class 1259 OID 16577)
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id bigint NOT NULL,
    institution_id integer NOT NULL,
    asset_id bigint NOT NULL,
    transaction_type text NOT NULL,
    from_user_id integer,
    to_user_id integer,
    from_department_id integer,
    to_department_id integer,
    from_location text,
    to_location text,
    remarks text,
    performed_by integer,
    performed_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 16576)
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO postgres;

--
-- TOC entry 5229 (class 0 OID 0)
-- Dependencies: 235
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- TOC entry 228 (class 1259 OID 16460)
-- Name: user_roles; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.user_roles (
    id integer NOT NULL,
    user_id integer NOT NULL,
    role_id integer NOT NULL,
    institution_id integer,
    assigned_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.user_roles OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 16459)
-- Name: user_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_roles_id_seq OWNER TO postgres;

--
-- TOC entry 5230 (class 0 OID 0)
-- Dependencies: 227
-- Name: user_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_roles_id_seq OWNED BY public.user_roles.id;


--
-- TOC entry 226 (class 1259 OID 16439)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    institution_id integer,
    username text NOT NULL,
    email text,
    password_hash text NOT NULL,
    first_name text,
    last_name text,
    is_active boolean DEFAULT true,
    created_at timestamp with time zone DEFAULT now(),
    last_login timestamp with time zone,
    updated_at timestamp with time zone DEFAULT now()
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 16438)
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- TOC entry 5231 (class 0 OID 0)
-- Dependencies: 225
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- TOC entry 4854 (class 2604 OID 16670)
-- Name: asset_documents id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents ALTER COLUMN id SET DEFAULT nextval('public.asset_documents_id_seq'::regclass);


--
-- TOC entry 4872 (class 2604 OID 25044)
-- Name: asset_images id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_images ALTER COLUMN id SET DEFAULT nextval('public.asset_images_id_seq'::regclass);


--
-- TOC entry 4860 (class 2604 OID 16729)
-- Name: asset_risk_scores id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_risk_scores ALTER COLUMN id SET DEFAULT nextval('public.asset_risk_scores_id_seq'::regclass);


--
-- TOC entry 4842 (class 2604 OID 16552)
-- Name: asset_tags id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_tags ALTER COLUMN id SET DEFAULT nextval('public.asset_tags_id_seq'::regclass);


--
-- TOC entry 4836 (class 2604 OID 16493)
-- Name: asset_types id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_types ALTER COLUMN id SET DEFAULT nextval('public.asset_types_id_seq'::regclass);


--
-- TOC entry 4837 (class 2604 OID 16510)
-- Name: assets id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets ALTER COLUMN id SET DEFAULT nextval('public.assets_id_seq'::regclass);


--
-- TOC entry 4862 (class 2604 OID 16753)
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- TOC entry 4865 (class 2604 OID 16805)
-- Name: checkout_requests id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checkout_requests ALTER COLUMN id SET DEFAULT nextval('public.checkout_requests_id_seq'::regclass);


--
-- TOC entry 4827 (class 2604 OID 16410)
-- Name: departments id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.departments ALTER COLUMN id SET DEFAULT nextval('public.departments_id_seq'::regclass);


--
-- TOC entry 4823 (class 2604 OID 16393)
-- Name: institutions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.institutions ALTER COLUMN id SET DEFAULT nextval('public.institutions_id_seq'::regclass);


--
-- TOC entry 4868 (class 2604 OID 24997)
-- Name: locations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations ALTER COLUMN id SET DEFAULT nextval('public.locations_id_seq'::regclass);


--
-- TOC entry 4848 (class 2604 OID 16632)
-- Name: maintenance_records id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records ALTER COLUMN id SET DEFAULT nextval('public.maintenance_records_id_seq'::regclass);


--
-- TOC entry 4856 (class 2604 OID 16700)
-- Name: predictive_features id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.predictive_features ALTER COLUMN id SET DEFAULT nextval('public.predictive_features_id_seq'::regclass);


--
-- TOC entry 4829 (class 2604 OID 16429)
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- TOC entry 4864 (class 2604 OID 16779)
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- TOC entry 4846 (class 2604 OID 16580)
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- TOC entry 4834 (class 2604 OID 16463)
-- Name: user_roles id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles ALTER COLUMN id SET DEFAULT nextval('public.user_roles_id_seq'::regclass);


--
-- TOC entry 4830 (class 2604 OID 16442)
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- TOC entry 5181 (class 0 OID 16667)
-- Dependencies: 240
-- Data for Name: asset_documents; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_documents (id, institution_id, asset_id, uploaded_by, filename, mimetype, object_path, size_bytes, uploaded_at) FROM stdin;
1	1	1	3	Dell_Latitude_5540_Warranty.pdf	application/pdf	nuni/assets/1/Dell_Latitude_5540_Warranty.pdf	250000	2025-12-16 03:51:43.40089+03
2	1	1	3	NUNI-LAP-001_Invoice.pdf	application/pdf	nuni/assets/1/NUNI-LAP-001_Invoice.pdf	180000	2025-12-16 03:51:43.40089+03
3	1	8	3	Server_Setup_Guide.pdf	application/pdf	nuni/assets/8/Server_Setup_Guide.pdf	320000	2025-12-16 03:51:43.40089+03
4	2	14	9	HP_ProLiant_Manual.pdf	application/pdf	kit/assets/14/HP_ProLiant_Manual.pdf	450000	2025-12-16 03:51:43.40089+03
\.


--
-- TOC entry 5195 (class 0 OID 25041)
-- Dependencies: 254
-- Data for Name: asset_images; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_images (id, asset_id, institution_id, file_name, mime_type, file_size, image_data, is_primary, uploaded_by, uploaded_at) FROM stdin;
\.


--
-- TOC entry 5185 (class 0 OID 16726)
-- Dependencies: 244
-- Data for Name: asset_risk_scores; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_risk_scores (id, institution_id, asset_id, risk_score, risk_level, predicted_failure_date, model_version, predicted_at) FROM stdin;
1	1	4	0.4200	MEDIUM	2026-02-14	v1.0	2025-12-16 03:51:43.40089+03
2	1	10	0.6500	HIGH	2026-01-15	v1.0	2025-12-16 03:51:43.40089+03
3	1	11	0.8200	HIGH	2025-12-31	v1.0	2025-12-16 03:51:43.40089+03
4	1	1	0.1500	LOW	\N	v1.0	2025-12-16 03:51:43.40089+03
5	2	14	0.2000	LOW	\N	v1.0	2025-12-16 03:51:43.40089+03
6	1	1	0.1227	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
7	1	2	0.1163	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
8	1	3	0.1163	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
9	1	7	0.0967	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
10	1	8	0.0933	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
11	1	9	0.0900	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
12	1	11	0.2867	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
13	1	10	0.2647	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
14	1	33	0.0000	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
15	1	35	0.0000	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
16	1	23	0.0030	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
17	1	5	0.1157	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
18	1	4	0.1720	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
19	1	12	0.0953	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
20	1	6	0.1030	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
21	1	38	0.0033	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
22	1	22	0.0180	LOW	\N	v1.0	2026-01-13 22:51:56.728074+03
23	1	1	0.1227	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
24	1	2	0.1163	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
25	1	3	0.1163	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
26	1	7	0.0967	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
27	1	8	0.0933	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
28	1	9	0.0900	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
29	1	11	0.2867	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
30	1	10	0.2647	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
31	1	33	0.0000	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
32	1	35	0.0000	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
33	1	23	0.0030	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
34	1	5	0.1157	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
35	1	4	0.1720	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
36	1	12	0.0953	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
37	1	6	0.1030	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
38	1	38	0.0033	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
39	1	22	0.0180	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
40	1	39	0.0000	LOW	\N	v1.0	2026-01-13 22:56:30.109791+03
41	1	1	0.1227	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
42	1	2	0.1163	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
43	1	3	0.1163	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
44	1	7	0.0967	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
45	1	8	0.0933	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
46	1	9	0.0900	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
47	1	11	0.2867	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
48	1	10	0.2647	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
49	1	33	0.0000	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
50	1	35	0.0000	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
51	1	23	0.0030	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
52	1	5	0.1157	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
53	1	4	0.1720	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
54	1	12	0.0953	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
55	1	6	0.1030	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
56	1	38	0.0033	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
57	1	22	0.0180	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
58	1	39	0.0000	LOW	\N	v1.0	2026-01-13 22:59:24.79737+03
59	1	1	0.1227	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
60	1	2	0.1163	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
61	1	3	0.1163	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
62	1	7	0.0967	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
63	1	8	0.0933	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
64	1	9	0.0900	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
65	1	11	0.2867	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
66	1	10	0.2647	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
67	1	33	0.0000	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
68	1	35	0.0000	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
69	1	23	0.0030	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
70	1	5	0.1157	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
71	1	4	0.1720	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
72	1	12	0.0953	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
73	1	6	0.1030	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
74	1	38	0.0033	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
75	1	22	0.0180	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
76	1	39	0.0000	LOW	\N	v1.0	2026-01-13 23:09:54.863141+03
77	1	1	0.1227	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
78	1	2	0.1163	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
79	1	3	0.1163	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
80	1	7	0.0967	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
81	1	8	0.0933	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
82	1	9	0.0900	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
83	1	11	0.2867	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
84	1	10	0.2647	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
85	1	33	0.0000	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
86	1	35	0.0000	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
87	1	23	0.0030	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
88	1	5	0.1157	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
89	1	4	0.1720	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
90	1	12	0.0953	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
91	1	6	0.1030	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
92	1	38	0.0033	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
93	1	22	0.0180	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
94	1	39	0.0000	LOW	\N	v1.0	2026-01-13 23:10:19.19602+03
95	1	1	0.1227	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
96	1	2	0.1163	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
97	1	3	0.1163	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
98	1	7	0.0967	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
99	1	8	0.0933	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
100	1	9	0.0900	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
101	1	11	0.2867	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
102	1	10	0.2647	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
103	1	33	0.0000	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
104	1	35	0.0000	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
105	1	23	0.0030	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
106	1	5	0.1157	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
107	1	6	0.1030	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
108	1	38	0.0033	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
109	1	22	0.0180	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
110	1	39	0.0000	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
111	1	4	0.1750	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
112	1	12	0.1073	LOW	\N	v1.0	2026-01-13 23:12:30.510925+03
113	1	1	0.1227	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
114	1	2	0.1163	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
115	1	3	0.1163	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
116	1	7	0.0967	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
117	1	8	0.0933	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
118	1	9	0.0933	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
119	1	11	0.2867	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
120	1	10	0.2647	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
121	1	33	0.0000	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
122	1	35	0.0000	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
123	1	23	0.0030	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
124	1	5	0.1157	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
125	1	6	0.1030	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
126	1	38	0.0033	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
127	1	22	0.0180	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
128	1	39	0.0000	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
129	1	4	0.1750	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
130	1	12	0.1073	LOW	\N	v1.0	2026-01-14 12:39:05.142199+03
131	1	33	0.0000	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
132	1	35	0.0000	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
133	1	23	0.0030	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
134	1	38	0.0033	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
135	1	22	0.0180	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
136	1	39	0.0000	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
137	1	2	0.1163	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
138	1	9	0.0933	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
139	1	3	0.1163	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
140	1	10	0.2647	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
141	1	4	0.1750	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
142	1	8	0.0933	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
143	1	11	0.2867	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
144	1	5	0.1157	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
145	1	6	0.1030	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
146	1	12	0.1073	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
147	1	1	0.1257	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
148	1	7	0.0997	LOW	\N	v1.0	2026-01-14 15:58:04.635552+03
149	2	16	0.0700	LOW	\N	v1.0	2026-01-14 17:25:59.547658+03
150	2	13	0.1300	LOW	\N	v1.0	2026-01-14 17:25:59.547658+03
151	2	14	0.0733	LOW	\N	v1.0	2026-01-14 17:25:59.547658+03
152	2	15	0.0700	LOW	\N	v1.0	2026-01-14 17:25:59.547658+03
153	1	33	0.0000	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
154	1	35	0.0000	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
155	1	23	0.0030	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
156	1	38	0.0033	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
157	1	22	0.0180	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
158	1	39	0.0000	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
159	1	2	0.1163	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
160	1	9	0.0933	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
161	1	3	0.1163	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
162	1	10	0.2647	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
163	1	4	0.1750	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
164	1	8	0.0933	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
165	1	11	0.2867	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
166	1	5	0.1157	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
167	1	6	0.1063	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
168	1	12	0.1073	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
169	1	1	0.1290	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
170	1	7	0.0997	LOW	\N	v1.0	2026-01-18 16:58:57.838926+03
171	1	33	0.0000	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
172	1	35	0.0000	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
173	1	23	0.0030	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
174	1	38	0.0033	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
175	1	22	0.0180	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
176	1	39	0.0000	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
177	1	2	0.1163	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
178	1	9	0.0933	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
179	1	3	0.1163	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
180	1	10	0.2647	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
181	1	4	0.1750	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
182	1	8	0.0933	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
183	1	11	0.2867	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
184	1	5	0.1157	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
185	1	6	0.1063	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
186	1	1	0.1290	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
187	1	7	0.0997	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
188	1	12	0.1573	LOW	\N	v1.0	2026-01-18 17:20:57.132392+03
189	1	33	0.0000	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
190	1	35	0.0000	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
191	1	23	0.0030	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
192	1	38	0.0067	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
193	1	22	0.0180	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
194	1	39	0.0000	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
195	1	2	0.1197	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
196	1	9	0.0933	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
197	1	3	0.1163	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
198	1	10	0.2647	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
199	1	4	0.1750	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
200	1	8	0.0933	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
201	1	11	0.2867	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
202	1	5	0.1157	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
203	1	6	0.1063	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
204	1	1	0.1290	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
205	1	7	0.1030	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
206	1	12	0.1573	LOW	\N	v1.0	2026-01-23 13:50:44.63842+03
\.


--
-- TOC entry 5175 (class 0 OID 16549)
-- Dependencies: 234
-- Data for Name: asset_tags; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_tags (id, institution_id, asset_id, tag_code, tag_type, issued_at, active) FROM stdin;
1	1	1	NUNI-LAP-001-QR	qr	2025-12-16 03:51:43.40089+03	t
2	1	2	NUNI-LAP-002-QR	qr	2025-12-16 03:51:43.40089+03	t
3	1	3	NUNI-DES-001-QR	qr	2025-12-16 03:51:43.40089+03	t
4	1	4	NUNI-DES-002-QR	qr	2025-12-16 03:51:43.40089+03	t
5	1	5	NUNI-PRT-001-QR	qr	2025-12-16 03:51:43.40089+03	t
6	1	6	NUNI-PRJ-001-QR	qr	2025-12-16 03:51:43.40089+03	t
7	1	7	NUNI-CAM-001-QR	qr	2025-12-16 03:51:43.40089+03	t
8	1	8	NUNI-SRV-001-QR	qr	2025-12-16 03:51:43.40089+03	t
9	1	9	NUNI-LAP-003-QR	qr	2025-12-16 03:51:43.40089+03	t
10	1	10	NUNI-DES-003-QR	qr	2025-12-16 03:51:43.40089+03	t
11	1	11	NUNI-LAP-004-QR	qr	2025-12-16 03:51:43.40089+03	t
12	1	12	NUNI-PRT-002-QR	qr	2025-12-16 03:51:43.40089+03	t
13	2	13	KIT-LAP-001-QR	qr	2025-12-16 03:51:43.40089+03	t
14	2	14	KIT-SRV-001-QR	qr	2025-12-16 03:51:43.40089+03	t
15	2	15	KIT-NET-001-QR	qr	2025-12-16 03:51:43.40089+03	t
16	2	16	KIT-LAP-002-QR	qr	2025-12-16 03:51:43.40089+03	t
17	3	17	DSP-EQU-001-QR	qr	2025-12-16 03:51:43.40089+03	t
18	3	18	DSP-TOOL-001-QR	qr	2025-12-16 03:51:43.40089+03	t
19	3	19	DSP-MAC-001-QR	qr	2025-12-16 03:51:43.40089+03	t
\.


--
-- TOC entry 5171 (class 0 OID 16490)
-- Dependencies: 230
-- Data for Name: asset_types; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.asset_types (id, institution_id, name, description) FROM stdin;
1	1	Laptop	Portable computers for staff/students
2	1	Desktop	Desktop computers for labs
3	1	Printer	Network printers
4	1	Projector	Lecture hall projectors
5	1	Server	Computing servers
6	1	Camera	Digital cameras for events
7	2	Laptop	Portable computers
8	2	Server	Computing servers
9	2	Network Equipment	Switches, routers, networking gear
10	3	Equipment	Laboratory equipment
11	3	Tools	Engineering tools
12	3	Machinery	Heavy machinery
13	1	Computer	Auto-created category
20	\N	Laptops 	
21	\N	Laptops 	
22	3	Laptop	Auto-created category
23	2	Printer	Auto-created from CSV import
24	2	Projector	Auto-created from CSV import
\.


--
-- TOC entry 5173 (class 0 OID 16507)
-- Dependencies: 232
-- Data for Name: assets; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.assets (id, institution_id, department_id, asset_type_id, asset_code, serial_number, name, description, acquisition_date, acquisition_cost, condition, status, current_holder_id, created_at, updated_at, date_retired, retirement_reason, location_id) FROM stdin;
33	1	\N	\N	NUNI-LAP-129	\N	HP Elitebook 830	\N	2026-01-06	30000.00	good	available	\N	2026-01-08 22:57:33.321491+03	2026-01-08 22:57:33.321491+03	\N	\N	\N
35	1	\N	1	NUNI-LAP-0312	\N	Dell Latitude 549	\N	2026-01-03	2200.00	good	available	\N	2026-01-08 22:59:18.885417+03	2026-01-08 22:59:18.885417+03	\N	\N	\N
23	1	\N	\N	ASSET-00016	\N	HP Elitebook 830	\N	\N	\N	good	on_loan	3	2026-01-08 19:39:33.842444+03	2026-01-09 10:33:27.590286+03	\N	\N	\N
38	1	\N	13	NUNI-LAP-009	\N	HP Elitebook 725 	\N	2025-11-20	0.00	good	retired	\N	2026-01-12 23:55:36.258956+03	2026-01-13 00:25:32.043722+03	2026-01-13 00:25:32.043722+03	End of useful life	\N
22	1	1	\N	ASSET-00015	\N	HP Elitebook 1030 G6	\N	\N	\N	good	available	\N	2026-01-08 19:37:44.810179+03	2026-01-13 11:48:46.438623+03	\N	\N	\N
39	1	\N	1	NUNI-LAP-028	\N	Dell Latitude 933	\N	\N	0.00	good	available	\N	2026-01-13 22:52:51.150567+03	2026-01-13 22:52:51.150567+03	\N	\N	\N
2	1	1	1	NUNI-LAP-002	SN-2023-002	HP EliteBook	Laptop for CS dept	2023-02-20	1300.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	1
9	1	1	1	NUNI-LAP-003	SN-2023-009	MacBook Pro 14	Developer laptop	2023-09-14	1800.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	1
3	1	1	2	NUNI-DES-001	SN-2023-003	HP Desktop PC	Lab computer	2023-03-10	800.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	2
4	1	2	2	NUNI-DES-002	SN-2023-004	ASUS Desktop	Engineering lab	2023-04-05	850.00	good	available	\N	2025-12-16 03:51:43.40089+03	2026-01-13 23:11:14.821113+03	\N	\N	3
8	1	1	5	NUNI-SRV-001	SN-2023-008	Dell PowerEdge R750	File server	2023-08-30	5000.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	6
5	1	3	3	NUNI-PRT-001	SN-2023-005	Canon imagePRESS	Network printer	2023-05-12	2500.00	good	available	\N	2025-12-16 03:51:43.40089+03	2026-01-10 01:19:30.77987+03	\N	\N	1
6	1	4	4	NUNI-PRJ-001	SN-2023-006	Epson EB-2250U	Lecture projector	2023-06-18	3000.00	good	available	\N	2025-12-16 03:51:43.40089+03	2026-01-12 20:54:16.627062+03	\N	\N	1
16	2	5	7	KIT-LAP-002	SN-2024-004	ASUS VivoBook	Lab computer	2024-04-12	700.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	7
13	2	5	7	KIT-LAP-001	SN-2024-001	Dell Latitude 5520	IT dept laptop	2024-01-10	1200.00	good	maintenance	\N	2025-12-16 03:51:43.40089+03	2026-01-09 09:13:03.341619+03	\N	\N	7
14	2	5	8	KIT-SRV-001	SN-2024-002	HP ProLiant DL380	Main server	2024-02-15	6000.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	8
15	2	6	9	KIT-NET-001	SN-2024-003	Cisco Catalyst 3850	Core switch	2024-03-20	4500.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	9
17	3	7	10	DSP-EQU-001	SN-2024-101	Oscilloscope	Lab equipment	2024-01-05	3500.00	good	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	10
18	3	8	11	DSP-TOOL-001	SN-2024-102	Lathe Machine	Workshop equipment	2024-02-10	8000.00	fair	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	11
19	3	9	11	DSP-MAC-001	SN-2024-103	CNC Machine	Heavy equipment	2024-03-18	15000.00	good	on_loan	12	2025-12-16 03:51:43.40089+03	2026-01-08 18:20:43.725696+03	\N	\N	11
1	1	1	1	NUNI-LAP-001	SN-2023-001	Dell Latitude 5540	Laptop for teaching	2023-01-15	1200.00	good	on_loan	4	2025-12-16 03:51:43.40089+03	2026-01-14 14:58:04.51533+03	\N	\N	5
7	1	2	6	NUNI-CAM-001	SN-2023-007	Canon EOS 5D	Digital camera	2023-07-22	1500.00	good	available	\N	2025-12-16 03:51:43.40089+03	2026-01-14 14:58:26.703825+03	\N	\N	3
41	2	\N	7	KIT-111	SN123456	Mac book 1020	Sample laptop for testing	2024-01-15	6000.00	good	available	\N	2026-01-16 20:49:12.801041+03	2026-01-16 20:49:12.801041+03	\N	\N	\N
42	2	\N	23	KIT-112	SN789012	HP lasejet printer	Sample printer	2024-02-20	3000.00	excellent	available	\N	2026-01-16 20:49:12.801041+03	2026-01-16 20:49:12.801041+03	\N	\N	\N
43	2	\N	24	KIT-113	SN210842	Dell Projector	Projector for use	2026-07-06	2000.00	good	available	\N	2026-01-16 20:49:12.801041+03	2026-01-16 20:49:12.801041+03	\N	\N	\N
40	3	\N	22	DSP-21823	\N	Mac book M4	\N	2026-01-13	75000.00	good	on_loan	12	2026-01-16 13:32:28.811186+03	2026-01-17 14:54:14.118859+03	\N	\N	12
10	1	2	2	NUNI-DES-003	SN-2023-010	Lenovo ThinkCentre	Workstation	2023-10-25	900.00	fair	available	3	2025-12-16 03:51:43.40089+03	2026-01-23 14:11:35.89676+03	\N	\N	3
12	1	3	3	NUNI-PRT-002	SN-2023-012	Brother HL-L8360	Department printer	2023-12-01	600.00	good	available	\N	2025-12-16 03:51:43.40089+03	2026-01-23 14:13:04.636142+03	\N	\N	1
11	1	1	1	NUNI-LAP-004	SN-2023-011	ThinkPad X1	Staff laptop	2023-11-08	1400.00	available	available	\N	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	\N	1
\.


--
-- TOC entry 5187 (class 0 OID 16750)
-- Dependencies: 246
-- Data for Name: audit_logs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.audit_logs (id, institution_id, user_id, entity_type, entity_id, action, old_values, new_values, details, created_at) FROM stdin;
1	1	3	assets	1	CREATE	\N	{"name": "Dell Latitude 5540", "status": "available", "asset_code": "NUNI-LAP-001"}	{"source": "asset_registration"}	2025-11-26 03:51:43.40089+03
2	1	2	transactions	1	CREATE	\N	{"asset_id": 1, "to_user_id": 6, "transaction_type": "check_out"}	{"source": "checkout"}	2025-12-11 03:51:43.40089+03
3	1	2	transactions	2	CREATE	\N	{"asset_id": 1, "from_user_id": 6, "transaction_type": "check_in"}	{"source": "checkin"}	2025-12-13 03:51:43.40089+03
4	1	3	maintenance_records	1	CREATE	\N	{"status": "open", "asset_id": 11, "description": "Screen flickering"}	{"source": "maintenance_request"}	2025-12-06 03:51:43.40089+03
5	1	3	maintenance_records	1	UPDATE	{"status": "open"}	{"status": "closed"}	{"reason": "Repaired"}	2025-12-08 03:51:43.40089+03
6	1	6	assets	1	UPDATE	{"status": "available"}	{"status": "on_loan"}	{"source": "checkout"}	2025-12-11 03:51:43.40089+03
7	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 22:09:33.633288+03
8	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 22:11:20.027082+03
9	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 22:12:18.759575+03
10	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 22:57:52.244829+03
11	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 23:57:51.884711+03
12	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 23:57:57.145615+03
13	\N	1	auth	1	LOGIN	null	null	\N	2025-12-22 23:58:32.899913+03
14	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:08:23.978353+03
15	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:09:17.751215+03
16	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:09:24.739899+03
17	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:12:21.006677+03
18	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:17:28.486138+03
19	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:19:39.703094+03
20	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 00:40:31.254612+03
21	\N	2	auth	2	LOGIN	null	null	\N	2025-12-23 08:52:22.144381+03
22	\N	2	auth	2	LOGIN	null	null	\N	2025-12-23 20:10:48.467714+03
23	\N	2	auth	2	LOGIN	null	null	\N	2025-12-23 20:12:11.553342+03
24	\N	1	auth	1	LOGIN	null	null	\N	2025-12-23 20:14:41.142186+03
25	\N	11	auth	11	LOGIN	null	null	\N	2025-12-23 20:15:53.229251+03
26	\N	11	auth	11	LOGIN	null	null	\N	2025-12-23 20:16:14.816337+03
27	\N	11	auth	11	LOGIN	null	null	\N	2025-12-23 20:17:09.76271+03
28	\N	11	auth	11	LOGIN	null	null	\N	2025-12-23 20:23:05.92437+03
29	\N	11	auth	11	LOGIN	null	null	\N	2025-12-23 20:27:02.899869+03
30	\N	7	auth	7	LOGIN	null	null	\N	2025-12-23 20:27:48.352564+03
31	\N	7	auth	7	LOGIN	null	null	\N	2025-12-23 20:35:32.604765+03
32	\N	7	auth	7	LOGIN	null	null	\N	2025-12-23 20:41:42.437478+03
33	\N	7	auth	7	LOGIN	null	null	\N	2025-12-23 20:54:08.951319+03
34	\N	7	auth	7	LOGIN	null	null	\N	2025-12-23 20:54:16.376505+03
35	\N	7	auth	7	LOGIN	null	null	\N	2025-12-23 21:03:40.585933+03
36	\N	7	auth	7	LOGIN	null	null	\N	2025-12-24 23:05:31.41131+03
37	\N	7	auth	7	LOGIN	null	null	\N	2025-12-24 23:16:17.219956+03
38	\N	7	auth	7	LOGIN	null	null	\N	2025-12-24 23:19:17.917974+03
39	\N	7	auth	7	LOGIN	null	null	\N	2025-12-24 23:54:02.174799+03
40	\N	7	auth	7	LOGIN	null	null	\N	2025-12-24 23:55:08.373692+03
41	\N	7	auth	7	LOGIN	null	null	\N	2025-12-24 23:57:21.395941+03
42	\N	7	auth	7	LOGIN	null	null	\N	2025-12-26 15:10:36.477593+03
43	\N	7	auth	7	LOGIN	null	null	\N	2025-12-26 15:13:47.63511+03
44	\N	7	auth	7	LOGIN	null	null	\N	2025-12-26 15:23:15.853233+03
45	\N	7	auth	7	LOGIN	null	null	\N	2025-12-26 16:14:40.080175+03
46	\N	7	auth	7	LOGIN	null	null	\N	2025-12-26 20:09:21.329469+03
47	\N	1	auth	1	LOGIN	null	null	\N	2025-12-26 21:20:27.271949+03
48	\N	1	auth	1	LOGIN	null	null	\N	2025-12-26 21:48:26.40316+03
49	\N	1	auth	1	LOGIN	null	null	\N	2025-12-26 21:48:39.622306+03
50	\N	1	auth	1	LOGIN	null	null	\N	2025-12-27 02:22:44.499662+03
51	\N	1	auth	1	LOGIN	null	null	\N	2025-12-27 02:31:55.371055+03
52	\N	1	auth	1	LOGIN	null	null	\N	2025-12-28 14:09:23.060669+03
53	\N	1	auth	1	LOGIN	null	null	\N	2025-12-28 14:16:02.176856+03
54	\N	1	auth	1	LOGIN	null	null	\N	2025-12-28 14:18:33.898563+03
55	\N	1	auth	1	LOGIN	null	null	\N	2025-12-29 19:51:08.34346+03
56	\N	1	assets	20	CREATE	null	{"id": 20, "name": "test", "status": "available", "condition": "good", "asset_code": "ASSET-00013", "created_at": "2025-12-29 22:33:47.261086+03", "updated_at": "2025-12-29 22:33:47.261086+03", "description": null, "asset_type_id": null, "department_id": null, "serial_number": null, "institution_id": 1, "acquisition_cost": null, "acquisition_date": null, "current_holder_id": null}	\N	2025-12-29 22:33:47.280945+03
57	\N	1	auth	1	LOGIN	null	null	\N	2025-12-30 12:31:28.428841+03
58	\N	1	assets	21	CREATE	null	{"id": 21, "name": "Lewis Simatwa", "status": "available", "condition": "good", "asset_code": "ASSET-00014", "created_at": "2025-12-30 13:53:50.693925+03", "updated_at": "2025-12-30 13:53:50.693925+03", "description": null, "asset_type_id": null, "department_id": null, "serial_number": null, "institution_id": 1, "acquisition_cost": null, "acquisition_date": null, "current_holder_id": null}	\N	2025-12-30 13:53:50.712161+03
59	\N	1	auth	1	LOGIN	null	null	\N	2025-12-30 13:59:09.665036+03
60	\N	1	auth	1	LOGIN	null	null	\N	2025-12-30 13:59:27.540298+03
61	\N	1	auth	1	LOGIN	null	null	\N	2025-12-30 13:59:35.438629+03
62	\N	1	auth	1	LOGIN	null	null	\N	2025-12-31 10:59:25.753415+03
63	\N	1	auth	1	LOGIN	null	null	\N	2025-12-31 12:15:40.790693+03
64	\N	1	auth	1	LOGIN	null	null	\N	2025-12-31 12:56:04.379129+03
65	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:01:36.377044+03
66	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:01:54.337364+03
67	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:10:49.309024+03
68	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:10:56.514847+03
69	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:17:03.734645+03
70	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:17:40.233414+03
71	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 13:53:46.481851+03
72	\N	9	auth	9	LOGIN	null	null	\N	2025-12-31 14:14:53.1432+03
73	\N	2	auth	2	LOGIN	null	null	\N	2025-12-31 14:15:10.703889+03
74	\N	2	auth	2	LOGIN	null	null	\N	2025-12-31 14:17:53.676322+03
75	\N	2	auth	2	LOGIN	null	null	\N	2026-01-04 18:56:41.940979+03
76	\N	2	auth	2	LOGIN	null	null	\N	2026-01-04 19:12:30.038006+03
77	\N	7	auth	7	LOGIN	null	null	\N	2026-01-04 19:13:25.955824+03
78	\N	9	auth	9	LOGIN	null	null	\N	2026-01-04 19:19:26.330111+03
79	\N	2	auth	2	LOGIN	null	null	\N	2026-01-05 18:34:14.783582+03
80	\N	9	auth	9	LOGIN	null	null	\N	2026-01-05 18:34:33.537852+03
81	\N	5	auth	5	LOGIN	null	null	\N	2026-01-05 18:35:37.611369+03
82	\N	9	auth	9	LOGIN	null	null	\N	2026-01-05 19:48:01.778128+03
83	\N	11	auth	11	LOGIN	null	null	\N	2026-01-05 19:49:35.935328+03
84	\N	6	auth	6	LOGIN	null	null	\N	2026-01-05 19:51:07.300314+03
85	\N	6	auth	6	LOGIN	null	null	\N	2026-01-05 22:41:54.12694+03
86	\N	6	auth	6	LOGIN	null	null	\N	2026-01-05 23:00:28.524613+03
87	\N	6	auth	6	LOGIN	null	null	\N	2026-01-05 23:01:16.046266+03
88	\N	11	auth	11	LOGIN	null	null	\N	2026-01-05 23:02:34.076112+03
89	\N	6	auth	6	LOGIN	null	null	\N	2026-01-05 23:02:48.172573+03
90	\N	6	auth	6	LOGIN	null	null	\N	2026-01-05 23:06:15.131709+03
91	\N	9	auth	9	LOGIN	null	null	\N	2026-01-05 23:07:37.454625+03
92	\N	9	auth	9	LOGIN	null	null	\N	2026-01-05 23:26:19.75891+03
93	\N	9	auth	9	LOGIN	null	null	\N	2026-01-06 20:39:03.023363+03
94	\N	9	auth	9	LOGIN	null	null	\N	2026-01-08 15:15:34.72568+03
95	\N	6	auth	6	LOGIN	null	null	\N	2026-01-08 15:19:56.645038+03
96	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 15:28:26.785349+03
97	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 16:54:01.9304+03
98	1	1	assets	4	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Mary Staff", "current_holder_id": "6"}	{"remarks": "For classroom use", "location": "Room 201"}	2026-01-08 17:29:06.436607+03
99	1	1	assets	4	CHECK_IN	{"status": "on_loan", "condition": "fair", "current_holder_id": 6}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": "Returned in good condition "}	2026-01-08 17:35:18.515959+03
100	1	1	assets	5	TRANSFER	{"department_id": 1, "department_name": "Computer Science"}	{"department_id": "3", "department_name": "Business Administration"}	{"remarks": "To use in printing ", "location": "Floor 5, room 8"}	2026-01-08 17:42:16.573086+03
101	1	1	assets	5	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "John Admin", "current_holder_id": "1"}	{"remarks": "", "location": "Room 313"}	2026-01-08 17:42:47.006786+03
102	1	1	assets	10	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Ahmed ICT", "current_holder_id": "3"}	{"remarks": "", "location": "room 32"}	2026-01-08 17:43:52.590573+03
103	1	1	maintenance_records	4	CREATE	\N	{"status": "open", "asset_id": "10", "asset_name": "Lenovo ThinkCentre", "maintenance_type": "preventive"}	{"start_date": "2026-01-15", "description": "Check storage usage "}	2026-01-08 17:55:33.637917+03
104	\N	2	auth	2	LOGIN	null	null	\N	2026-01-08 18:06:17.379701+03
105	\N	5	auth	5	LOGIN	null	null	\N	2026-01-08 18:16:32.01579+03
106	\N	4	auth	4	LOGIN	null	null	\N	2026-01-08 18:18:59.348072+03
107	\N	11	auth	11	LOGIN	null	null	\N	2026-01-08 18:19:43.15114+03
108	3	11	assets	19	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Hassan Security", "current_holder_id": "12"}	{"remarks": "Check firewalls", "location": "Lab B"}	2026-01-08 18:20:43.725696+03
109	\N	4	auth	4	LOGIN	null	null	\N	2026-01-08 18:35:51.552596+03
110	1	4	assets	12	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Mary Staff", "current_holder_id": "6"}	{"remarks": "", "location": ""}	2026-01-08 18:49:14.514825+03
111	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 18:49:27.121197+03
112	\N	1	assets	22	CREATE	null	{"id": 22, "name": "HP Elitebook 1030 G6", "status": "available", "condition": "good", "asset_code": "ASSET-00015", "created_at": "2026-01-08 19:37:44.810179+03", "updated_at": "2026-01-08 19:37:44.810179+03", "description": null, "asset_type_id": null, "department_id": null, "serial_number": null, "institution_id": 1, "acquisition_cost": null, "acquisition_date": null, "current_holder_id": null}	\N	2026-01-08 19:37:44.829598+03
113	\N	1	assets	23	CREATE	null	{"id": 23, "name": "HP Elitebook 830", "status": "available", "condition": "good", "asset_code": "ASSET-00016", "created_at": "2026-01-08 19:39:33.842444+03", "updated_at": "2026-01-08 19:39:33.842444+03", "description": null, "asset_type_id": null, "department_id": null, "serial_number": null, "institution_id": 1, "acquisition_cost": null, "acquisition_date": null, "current_holder_id": null}	\N	2026-01-08 19:39:33.857595+03
114	\N	7	auth	7	LOGIN	null	null	\N	2026-01-08 19:49:15.394143+03
115	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 19:53:36.22134+03
116	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 21:20:53.782437+03
117	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 21:21:22.174816+03
118	\N	9	auth	9	LOGIN	null	null	\N	2026-01-08 21:21:58.343059+03
119	\N	9	auth	9	LOGIN	null	null	\N	2026-01-08 22:00:22.911313+03
120	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 22:06:47.119153+03
121	\N	4	auth	4	LOGIN	null	null	\N	2026-01-08 22:08:12.275469+03
122	\N	2	auth	2	LOGIN	null	null	\N	2026-01-08 22:08:31.817983+03
123	\N	4	auth	4	LOGIN	null	null	\N	2026-01-08 22:08:46.088546+03
124	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 22:09:04.355686+03
128	1	1	assets	12	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 6}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-08 22:38:39.848901+03
129	1	1	assets	21	DELETE	{"id": 21, "name": "Lewis Simatwa", "status": "available", "category": null, "asset_code": "ASSET-00014"}	\N	\N	2026-01-08 22:40:30.280417+03
130	1	1	assets	20	DELETE	{"id": 20, "name": "test", "status": "available", "category": null, "asset_code": "ASSET-00013"}	\N	\N	2026-01-08 22:40:55.013862+03
131	1	1	assets	33	CREATE	\N	{"name": "HP Elitebook 830", "status": "available", "asset_code": "NUNI-LAP-129"}	\N	2026-01-08 22:57:33.321491+03
132	1	1	assets	34	CREATE	\N	{"name": "wqudguqwefw", "status": "available", "asset_code": "ewfwfrw"}	\N	2026-01-08 22:58:07.921061+03
133	1	1	assets	34	DELETE	{"id": 34, "name": "wqudguqwefw", "status": "available", "category": null, "asset_code": "ewfwfrw"}	\N	\N	2026-01-08 22:58:39.131429+03
134	1	1	assets	35	CREATE	\N	{"name": "Dell Latitude 549", "status": "available", "asset_code": "NUNI-LAP-0312"}	\N	2026-01-08 22:59:18.885417+03
135	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 23:02:00.833382+03
136	\N	1	auth	1	LOGIN	null	null	\N	2026-01-08 23:38:58.807969+03
137	\N	2	auth	2	LOGIN	null	null	\N	2026-01-09 00:07:10.567439+03
138	\N	16	auth	16	LOGIN	null	null	\N	2026-01-09 00:07:26.461605+03
139	\N	17	auth	17	LOGIN	null	null	\N	2026-01-09 00:08:31.395231+03
140	\N	2	auth	2	LOGIN	null	null	\N	2026-01-09 00:15:46.475954+03
141	\N	2	auth	2	LOGIN	null	null	\N	2026-01-09 09:08:24.021602+03
142	\N	2	auth	2	LOGIN	null	null	\N	2026-01-09 09:11:05.573312+03
143	\N	2	auth	2	LOGIN	null	null	\N	2026-01-09 09:11:29.079691+03
144	\N	9	auth	9	LOGIN	null	null	\N	2026-01-09 09:11:48.043409+03
145	\N	9	auth	9	LOGIN	null	null	\N	2026-01-09 09:12:17.815215+03
146	2	9	maintenance_records	5	CREATE	\N	{"cost": 300, "status": "open", "asset_id": "13", "asset_name": "Dell Latitude 5520", "maintenance_type": "corrective"}	{"start_date": "2026-01-08", "description": "Change RAM"}	2026-01-09 09:13:03.341619+03
147	\N	9	auth	9	LOGIN	null	null	\N	2026-01-09 09:17:26.693169+03
148	\N	9	auth	9	LOGIN	null	null	\N	2026-01-09 09:34:34.935312+03
149	\N	1	auth	1	LOGIN	null	null	\N	2026-01-09 09:35:08.125945+03
150	\N	1	auth	1	LOGIN	null	null	\N	2026-01-09 10:19:58.84333+03
151	1	1	assets	23	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Ahmed ICT", "current_holder_id": "3"}	{"remarks": "", "location": "Room 31"}	2026-01-09 10:33:27.590286+03
152	\N	6	auth	6	LOGIN	null	null	\N	2026-01-09 10:37:52.235796+03
153	\N	1	auth	1	LOGIN	null	null	\N	2026-01-09 10:54:50.641283+03
154	\N	9	auth	9	LOGIN	null	null	\N	2026-01-09 10:57:16.034114+03
155	\N	1	auth	1	LOGIN	null	null	\N	2026-01-09 11:18:12.391902+03
156	\N	1	auth	1	LOGIN	null	null	\N	2026-01-09 11:46:27.964952+03
157	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 00:17:41.340362+03
158	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 00:23:45.637725+03
159	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 00:27:13.754823+03
160	1	1	users	18	CREATE	\N	{"email": "simatwalewiss@gmail.com", "role_id": "4", "username": "smart"}	{"source": "user_management"}	2026-01-10 00:31:58.856599+03
161	1	1	users	18	DELETE	{"email": "simatwalewiss@gmail.com", "username": "smart"}	\N	{"source": "user_management"}	2026-01-10 00:32:22.466873+03
162	\N	5	auth	5	LOGIN	null	null	\N	2026-01-10 00:35:36.431718+03
163	\N	2	auth	2	LOGIN	null	null	\N	2026-01-10 00:35:55.611737+03
164	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 00:40:23.702765+03
165	1	1	users	16	UPDATE	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"source": "user_management"}	2026-01-10 00:45:18.913116+03
166	1	1	users	16	UPDATE	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"source": "user_management"}	2026-01-10 00:45:30.097694+03
167	1	1	users	16	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-10 00:45:33.728306+03
168	1	1	users	16	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-10 00:45:37.27239+03
169	1	1	users	16	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-10 00:46:06.612156+03
170	1	1	users	16	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-10 00:46:08.167213+03
171	\N	7	auth	7	LOGIN	null	null	\N	2026-01-10 00:48:21.163352+03
172	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 00:48:42.997829+03
173	1	1	users	16	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-10 00:48:51.376065+03
174	\N	16	auth	16	LOGIN	null	null	\N	2026-01-10 00:49:04.213793+03
175	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 00:49:18.206184+03
176	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 01:01:47.713532+03
177	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 01:05:21.815496+03
178	1	1	users	16	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-10 01:13:37.038044+03
179	1	1	assets	5	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 1}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-10 01:19:30.77987+03
180	1	1	assets	4	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Lewis Simatwa", "current_holder_id": "16"}	{"remarks": "", "location": "Room 01"}	2026-01-10 01:19:46.396451+03
181	\N	7	auth	7	LOGIN	null	null	\N	2026-01-10 14:25:46.163431+03
182	\N	11	auth	11	LOGIN	null	null	\N	2026-01-10 14:26:06.537707+03
183	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 14:29:49.738274+03
184	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 15:11:41.231965+03
185	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 15:24:46.365662+03
186	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 15:25:35.213784+03
187	1	1	assets	36	CREATE	\N	{"name": "werty", "status": "available", "asset_code": "qwert.gg"}	\N	2026-01-10 15:27:28.618819+03
188	1	1	assets	36	DELETE	{"id": 36, "name": "werty", "status": "available", "category": null, "asset_code": "qwert.gg"}	\N	\N	2026-01-10 15:32:13.568822+03
189	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 18:54:33.203698+03
190	\N	1	auth	1	LOGIN	null	null	\N	2026-01-10 21:48:42.166065+03
191	1	1	assets	37	CREATE	\N	{"name": "HP Elitebook 830 g3", "status": "available", "asset_code": "NUNI-LAP-0313"}	\N	2026-01-10 21:49:59.404786+03
192	1	1	users	5	UPDATE	{"email": "manager@cs.nuni.ac.ke", "is_active": true, "last_name": "Manager", "first_name": "David"}	{"email": "manager@cs.nuni.ac.ke", "is_active": true, "last_name": "Manager", "first_name": "David"}	{"source": "user_management"}	2026-01-10 23:18:27.893851+03
193	1	1	assets	37	DELETE	{"id": 37, "name": "HP Elitebook 830 g3", "status": "available", "category": null, "asset_code": "NUNI-LAP-0313"}	\N	\N	2026-01-10 23:20:16.546801+03
194	1	1	users	16	UPDATE	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"source": "user_management"}	2026-01-10 23:26:07.081983+03
195	\N	16	auth	16	LOGIN	null	null	\N	2026-01-10 23:38:28.451242+03
196	\N	16	auth	16	LOGIN	null	null	\N	2026-01-12 15:50:43.94665+03
197	\N	1	auth	1	LOGIN	null	null	\N	2026-01-12 15:51:19.207786+03
198	\N	5	auth	5	LOGIN	null	null	\N	2026-01-12 15:53:30.218433+03
199	1	5	assets	22	TRANSFER	{"department_id": null, "department_name": null}	{"department_id": "4", "department_name": "Library Services"}	{"remarks": "Research in Library ", "location": "Library "}	2026-01-12 15:54:19.784638+03
200	\N	1	auth	1	LOGIN	null	null	\N	2026-01-12 15:55:27.907691+03
201	\N	2	auth	2	LOGIN	null	null	\N	2026-01-12 15:55:46.193848+03
202	\N	4	auth	4	LOGIN	null	null	\N	2026-01-12 15:56:11.605888+03
203	\N	5	auth	5	LOGIN	null	null	\N	2026-01-12 16:08:03.717009+03
204	\N	2	auth	2	LOGIN	null	null	\N	2026-01-12 16:16:29.578545+03
205	\N	4	auth	4	LOGIN	null	null	\N	2026-01-12 16:16:47.713018+03
206	\N	1	auth	1	LOGIN	null	null	\N	2026-01-12 16:17:51.144835+03
207	\N	1	auth	1	LOGIN	null	null	\N	2026-01-12 16:24:35.203614+03
208	1	1	assets	22	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Mary Staff", "current_holder_id": "6"}	{"remarks": "", "location": "Lec 039"}	2026-01-12 16:26:47.792249+03
209	1	1	assets	22	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 6}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-12 16:27:13.44571+03
210	1	1	assets	12	TRANSFER	{"department_id": 3, "department_name": "Business Administration"}	{"department_id": "1", "department_name": "Computer Science"}	{"remarks": "", "location": "Floor 4"}	2026-01-12 20:39:42.699548+03
211	1	1	assets	12	TRANSFER	{"department_id": 1, "department_name": "Computer Science"}	{"department_id": "3", "department_name": "Business Administration"}	{"remarks": "", "location": ""}	2026-01-12 20:40:33.566915+03
212	1	1	assets	6	TRANSFER	{"department_id": 3, "department_name": "Business Administration"}	{"department_id": "4", "department_name": "Library Services"}	{"remarks": "", "location": ""}	2026-01-12 20:54:16.627062+03
213	1	1	assets	38	CREATE	\N	{"name": "HP Elitebook 725 ", "status": "available", "asset_code": "NUNI-LAP-009"}	\N	2026-01-12 23:55:36.258956+03
214	\N	4	auth	4	LOGIN	null	null	\N	2026-01-12 23:55:59.611262+03
215	\N	1	auth	1	LOGIN	null	null	\N	2026-01-12 23:56:14.167904+03
216	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 00:13:52.037519+03
217	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 00:23:14.249569+03
218	1	1	assets	38	RETIRE	{"status": "available", "date_retired": null, "retirement_reason": null}	{"status": "retired", "date_retired": "2026-01-13 00:25:32.043722+03", "retirement_reason": "End of useful life"}	{"asset_code": "NUNI-LAP-009", "asset_name": "HP Elitebook 725 ", "retired_by": 1, "retirement_reason": "End of useful life"}	2026-01-13 00:25:32.043722+03
219	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 11:40:36.654134+03
220	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 11:41:48.660347+03
221	1	1	assets	22	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "David Manager", "current_holder_id": "5"}	{"remarks": "", "location": "TH 07"}	2026-01-13 11:46:00.735736+03
222	1	1	assets	22	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 5}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-13 11:47:49.982082+03
223	1	1	assets	22	TRANSFER	{"department_id": 4, "department_name": "Library Services"}	{"department_id": "1", "department_name": "Computer Science"}	{"remarks": "", "location": "Lab 4"}	2026-01-13 11:48:46.438623+03
224	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 20:14:40.044147+03
225	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 20:32:00.442321+03
226	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 21:51:09.981162+03
227	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 22:35:57.109836+03
228	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 22:50:14.760787+03
229	\N	1	auth	1	LOGIN	null	null	\N	2026-01-13 22:50:38.460457+03
230	1	1	assets	39	CREATE	\N	{"name": "Dell Latitude 933", "status": "available", "asset_code": "NUNI-LAP-028"}	\N	2026-01-13 22:52:51.150567+03
231	1	1	assets	4	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 16}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-13 23:11:14.821113+03
232	1	1	assets	12	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Mary Staff", "current_holder_id": "6"}	{"remarks": "", "location": ""}	2026-01-13 23:11:54.274624+03
233	1	1	assets	12	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 6}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-13 23:12:03.480424+03
234	1	1	assets	12	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Lewis Simatwa", "current_holder_id": "16"}	{"remarks": "", "location": ""}	2026-01-13 23:12:12.433194+03
235	1	1	assets	12	CHECK_IN	{"status": "on_loan", "condition": "good", "current_holder_id": 16}	{"status": "available", "condition": "good", "current_holder_id": null}	{"remarks": ""}	2026-01-13 23:12:19.002433+03
236	\N	1	auth	1	LOGIN	null	null	\N	2026-01-14 13:18:08.245695+03
237	1	1	assets	1	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Grace Auditor", "location_id": "5", "current_holder_id": "4"}	{"remarks": "", "location": "Admin Office - Administration Block, Floor 3, Room 301"}	2026-01-14 14:58:04.51533+03
238	1	1	assets	7	TRANSFER	{"department_id": 4, "department_name": "Library Services"}	{"location_id": "3", "department_id": "2", "department_name": "Electrical Engineering"}	{"remarks": "", "location": "EE Workshop - Engineering Building, Floor 1, Room 105"}	2026-01-14 14:58:26.703825+03
239	\N	1	auth	1	LOGIN	null	null	\N	2026-01-14 16:49:44.056335+03
240	\N	1	auth	1	LOGIN	null	null	\N	2026-01-14 16:54:27.72046+03
241	\N	1	auth	1	LOGIN	null	null	\N	2026-01-14 16:59:20.343448+03
242	\N	1	auth	1	LOGIN	null	null	\N	2026-01-14 17:01:09.800968+03
243	\N	11	auth	11	LOGIN	null	null	\N	2026-01-14 17:03:59.970108+03
244	\N	2	auth	2	LOGIN	null	null	\N	2026-01-14 17:20:19.541437+03
245	\N	11	auth	11	LOGIN	null	null	\N	2026-01-14 17:21:41.955839+03
246	\N	9	auth	9	LOGIN	null	null	\N	2026-01-14 17:25:32.318605+03
247	\N	1	auth	1	LOGIN	null	null	\N	2026-01-14 21:59:06.208594+03
248	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 15:26:30.508377+03
249	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 15:51:14.553181+03
250	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 16:21:31.018894+03
251	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 16:44:50.437439+03
252	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:48:24.177371+03
253	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:48:32.046413+03
254	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:48:44.455461+03
255	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:49:10.688232+03
256	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:54:58.357463+03
257	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:55:38.735119+03
258	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:55:43.896175+03
259	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:55:52.546262+03
260	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:56:11.86556+03
261	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:56:20.992303+03
262	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:56:35.415441+03
263	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:56:40.879613+03
264	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:58:21.447692+03
265	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:58:24.162731+03
266	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:58:31.670267+03
267	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 17:58:36.99527+03
268	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:58:51.747352+03
269	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 17:59:30.937825+03
270	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:00:03.83408+03
271	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:04:30.511085+03
272	\N	2	auth	2	LOGIN	null	null	\N	2026-01-15 18:04:44.451239+03
273	\N	2	auth	2	LOGIN	null	null	\N	2026-01-15 18:05:01.838303+03
274	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:06:04.674435+03
275	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:06:23.083954+03
276	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:07:49.754193+03
277	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:14:59.714216+03
278	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:15:17.616722+03
279	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:15:35.756999+03
280	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:15:56.910395+03
281	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:16:06.41007+03
282	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:20:53.042434+03
283	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:21:54.179886+03
284	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:23:55.746937+03
285	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:24:06.2161+03
286	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:24:22.26018+03
287	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:25:40.386802+03
288	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:26:10.836399+03
289	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:28:43.484125+03
290	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:29:38.496385+03
291	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:33:18.303116+03
292	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:34:23.77735+03
293	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:36:19.981138+03
294	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:36:53.460291+03
295	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:37:28.684422+03
296	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:37:42.059396+03
297	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 18:40:02.889795+03
298	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:40:15.935273+03
299	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:41:37.784779+03
300	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 18:45:42.557551+03
301	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 20:20:09.634637+03
302	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 20:45:58.806139+03
303	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 22:01:15.093982+03
304	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 22:02:13.446276+03
305	\N	1	auth	1	LOGIN	null	null	\N	2026-01-15 22:39:27.30449+03
306	\N	2	auth	2	LOGIN	null	null	\N	2026-01-15 22:40:06.132514+03
307	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 22:40:18.839131+03
308	\N	2	auth	2	LOGIN	null	null	\N	2026-01-15 22:46:27.358446+03
309	\N	2	auth	2	LOGIN	null	null	\N	2026-01-15 22:47:34.818361+03
310	\N	19	auth	19	LOGIN	null	null	\N	2026-01-15 22:47:52.707133+03
311	1	19	system_config	\N	UPDATE	\N	[{"id": 1768554331634, "name": "Laptops ", "description": ""}]	{"section": "asset_categories", "updated_by": "super_admin"}	2026-01-16 12:05:32.020672+03
312	1	19	system_config	\N	UPDATE	\N	[{"id": 1768554331634, "name": "Laptops ", "description": ""}, {"id": 1768554331852, "name": "Laptops ", "description": ""}]	{"section": "asset_categories", "updated_by": "super_admin"}	2026-01-16 12:05:32.347269+03
313	1	19	system_config	\N	UPDATE	\N	[{"id": 1768554331634, "name": "Laptops ", "description": ""}, {"id": 1768554331852, "name": "Laptops ", "description": ""}, {"id": 1768554344447, "name": "Furniture", "description": ""}]	{"section": "asset_categories", "updated_by": "super_admin"}	2026-01-16 12:05:44.895412+03
314	1	19	system_config	\N	UPDATE	\N	[{"id": 1768554331634, "name": "Laptops ", "description": ""}, {"id": 1768554331852, "name": "Laptops ", "description": ""}]	{"section": "asset_categories", "updated_by": "super_admin"}	2026-01-16 12:05:49.836765+03
315	1	19	system_config	\N	UPDATE	\N	[{"id": 1, "name": "available", "color": "#802323", "description": "Asset is available for use"}, {"id": 2, "name": "on_loan", "color": "#3b82f6", "description": "Asset is checked out"}, {"id": 3, "name": "maintenance", "color": "#f59e0b", "description": "Asset is under maintenance"}, {"id": 4, "name": "retired", "color": "#ef4444", "description": "Asset is retired"}]	{"section": "global_statuses", "updated_by": "super_admin"}	2026-01-16 12:06:08.060259+03
316	1	19	system_config	\N	UPDATE	\N	[{"id": 1, "name": "available", "color": "#10b981", "description": "Asset is available for use"}, {"id": 2, "name": "on_loan", "color": "#3b82f6", "description": "Asset is checked out"}, {"id": 3, "name": "maintenance", "color": "#f59e0b", "description": "Asset is under maintenance"}, {"id": 4, "name": "retired", "color": "#ef4444", "description": "Asset is retired"}]	{"section": "global_statuses", "updated_by": "super_admin"}	2026-01-16 13:30:38.332718+03
317	\N	11	auth	11	LOGIN	null	null	\N	2026-01-16 13:31:26.802483+03
318	3	11	assets	40	CREATE	\N	{"name": "Mac book M4", "status": "available", "asset_code": "DSP-21823"}	\N	2026-01-16 13:32:28.811186+03
319	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 13:33:10.825875+03
320	\N	2	auth	2	LOGIN	null	null	\N	2026-01-16 17:46:26.740534+03
321	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 17:52:21.90132+03
322	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 18:31:50.367452+03
323	\N	19	institution	4	CREATE_INSTITUTION	null	"{\\"name\\":\\"Catholic University \\",\\"code\\":\\"CUEA\\",\\"address\\":\\"Bogani road 93104\\",\\"contact_email\\":\\"info@cuea.edu\\"}"	\N	2026-01-16 18:44:44.016615+03
324	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 18:49:12.887184+03
325	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:41:58.260993+03
326	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:42:35.161633+03
327	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:43:01.473148+03
328	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:43:21.824729+03
329	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "total_rows": 3, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:44:51.573964+03
330	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "total_rows": 3, "institution_id": 3, "institution_name": "Dar es Salaam Polytechnic"}	2026-01-16 20:45:33.877061+03
331	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "total_rows": 3, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:45:54.4368+03
332	\N	9	auth	9	LOGIN	null	null	\N	2026-01-16 20:46:41.083106+03
333	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 20:47:14.487668+03
334	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 3, "imported": 0, "total_rows": 3, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:48:41.054868+03
335	\N	19	assets	\N	CSV_IMPORT	\N	\N	{"failed": 0, "imported": 3, "total_rows": 3, "institution_id": 2, "institution_name": "Kampala Institute of Technology"}	2026-01-16 20:49:12.801041+03
336	\N	9	auth	9	LOGIN	null	null	\N	2026-01-16 20:50:46.392794+03
337	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 20:51:16.375963+03
338	1	\N	institutions	1	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:39.044189+03:00"}	2026-01-16 20:53:39.044189+03
339	2	\N	institutions	2	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:39.044189+03:00"}	2026-01-16 20:53:39.044189+03
340	3	\N	institutions	3	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:39.044189+03:00"}	2026-01-16 20:53:39.044189+03
341	4	\N	institutions	4	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:39.044189+03:00"}	2026-01-16 20:53:39.044189+03
342	1	\N	institutions	1	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:47.745035+03:00"}	2026-01-16 20:53:47.745035+03
343	2	\N	institutions	2	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:47.745035+03:00"}	2026-01-16 20:53:47.745035+03
344	3	\N	institutions	3	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:47.745035+03:00"}	2026-01-16 20:53:47.745035+03
345	4	\N	institutions	4	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:53:47.745035+03:00"}	2026-01-16 20:53:47.745035+03
346	1	\N	institutions	1	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:55:13.072284+03:00"}	2026-01-16 20:55:13.072284+03
347	2	\N	institutions	2	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:55:13.072284+03:00"}	2026-01-16 20:55:13.072284+03
348	3	\N	institutions	3	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:55:13.072284+03:00"}	2026-01-16 20:55:13.072284+03
349	4	\N	institutions	4	SCHEMA_UPDATE	\N	\N	{"change": "Added is_active column", "default_value": true, "migration_date": "2026-01-16T20:55:13.072284+03:00"}	2026-01-16 20:55:13.072284+03
350	\N	9	auth	9	LOGIN	null	null	\N	2026-01-16 21:10:45.410341+03
351	\N	1	auth	1	LOGIN	null	null	\N	2026-01-16 21:10:59.581097+03
352	\N	19	auth	19	LOGIN	null	null	\N	2026-01-16 21:17:02.839264+03
353	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 10:26:17.258241+03
354	4	19	institutions	4	DEACTIVATE	{"is_active": true}	{"is_active": false}	{"name": "Catholic University "}	2026-01-17 11:23:17.601857+03
355	4	19	institutions	4	REACTIVATE	{"is_active": false}	{"is_active": true}	{"name": "Catholic University "}	2026-01-17 11:23:27.211543+03
356	4	19	institutions	4	DEACTIVATE	{"is_active": true}	{"is_active": false}	{"name": "Catholic University "}	2026-01-17 11:37:19.630319+03
357	4	19	institutions	4	REACTIVATE	{"is_active": false}	{"is_active": true}	{"name": "Catholic University "}	2026-01-17 11:37:26.02936+03
358	\N	22	auth	22	LOGIN	null	null	\N	2026-01-17 11:55:57.168677+03
359	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 11:56:50.513936+03
360	\N	1	auth	1	LOGIN	null	null	\N	2026-01-17 11:58:20.00029+03
361	1	1	users	16	UPDATE	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"email": "lewissimatwa@gmail.com", "is_active": true, "last_name": "Simatwa", "first_name": "Lewis"}	{"source": "user_management"}	2026-01-17 11:58:44.99837+03
362	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 12:10:35.85893+03
363	4	19	institutions	4	DEACTIVATE	{"is_active": true}	{"is_active": false}	{"name": "Catholic University "}	2026-01-17 12:13:13.468028+03
364	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 12:13:26.303093+03
365	\N	19	institution	5	CREATE_INSTITUTION	null	"{\\"name\\":\\"Daystar University \\",\\"code\\":\\"DSU\\",\\"address\\":\\"AthiRiver Machakos\\",\\"contact_email\\":\\"info@daystar.edu\\"}"	\N	2026-01-17 12:14:27.294864+03
366	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 12:15:11.899349+03
367	\N	1	auth	1	LOGIN	null	null	\N	2026-01-17 12:18:08.805435+03
368	\N	2	auth	2	LOGIN	null	null	\N	2026-01-17 12:19:39.049061+03
369	\N	1	auth	1	LOGIN	null	null	\N	2026-01-17 12:20:00.547696+03
370	1	1	assets	\N	EXPORT_CSV	\N	\N	{"total_assets": 18}	2026-01-17 12:38:48.675528+03
371	\N	2	auth	2	LOGIN	null	null	\N	2026-01-17 12:39:08.346483+03
372	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 12:41:46.571647+03
373	3	19	institutions	3	REACTIVATE	{"is_active": true}	{"is_active": true}	{"name": "Dar es Salaam Polytechnic"}	2026-01-17 13:11:17.110835+03
374	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:19:46.220139+03
375	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:20:11.372587+03
376	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:20:48.999767+03
377	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:21:16.278889+03
378	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:21:34.80755+03
379	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:22:16.348515+03
380	\N	19	analytics	\N	EXPORT_ANALYTICS	\N	\N	{"total_assets": 29}	2026-01-17 13:26:58.727457+03
381	\N	19	audit_logs	\N	EXPORT_AUDIT_LOGS	\N	\N	{"total_logs": 68}	2026-01-17 13:27:39.698647+03
382	4	19	institutions	4	REACTIVATE	{"is_active": false}	{"is_active": true}	{"name": "Catholic University "}	2026-01-17 13:43:03.951964+03
383	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:48:53.896676+03
384	\N	11	auth	11	LOGIN	null	null	\N	2026-01-17 13:49:28.240715+03
385	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 13:50:52.840343+03
386	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 14:01:08.163323+03
387	\N	11	auth	11	LOGIN	null	null	\N	2026-01-17 14:08:12.240986+03
388	3	11	users	12	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-17 14:08:21.745785+03
389	3	11	users	12	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-17 14:08:26.393736+03
390	3	11	users	13	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-17 14:08:33.894501+03
391	3	11	users	13	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-17 14:08:36.862412+03
392	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 14:08:43.163721+03
393	\N	11	auth	11	LOGIN	null	null	\N	2026-01-17 14:17:26.380716+03
394	\N	19	auth	19	LOGIN	null	null	\N	2026-01-17 14:17:34.724418+03
395	3	11	auth	11	LOGIN	\N	\N	\N	2026-01-17 14:31:20.373655+03
396	3	11	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:31:20.385315+03
397	\N	19	auth	19	LOGIN	\N	\N	\N	2026-01-17 14:32:01.703047+03
398	\N	19	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:32:01.714933+03
399	3	11	auth	11	LOGIN	\N	\N	\N	2026-01-17 14:33:04.941855+03
400	3	11	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:33:04.955147+03
401	\N	19	auth	19	LOGIN	\N	\N	\N	2026-01-17 14:33:21.934782+03
402	\N	19	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:33:21.94004+03
403	3	11	auth	11	LOGIN	\N	\N	\N	2026-01-17 14:35:55.343014+03
404	3	11	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:35:55.356322+03
405	3	11	users	12	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-17 14:36:06.765222+03
406	3	11	users	12	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-17 14:36:09.58199+03
407	\N	19	auth	19	LOGIN	\N	\N	\N	2026-01-17 14:36:23.069923+03
408	\N	19	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:36:23.076794+03
409	\N	19	auth	19	LOGIN	\N	\N	\N	2026-01-17 14:39:30.565755+03
410	\N	19	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:39:30.577413+03
411	3	11	auth	11	LOGIN	\N	\N	\N	2026-01-17 14:40:04.368181+03
412	3	11	auth	\N	LOGIN	\N	\N	\N	2026-01-17 14:40:04.375104+03
413	1	1	users	3	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-17 14:50:42.898011+03
414	1	1	users	3	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-17 14:50:48.086431+03
415	1	1	users	2	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-17 14:50:53.158034+03
416	1	1	users	5	UPDATE	{"is_active": true}	{"is_active": false}	{"source": "user_management"}	2026-01-17 14:50:56.75503+03
417	1	1	users	5	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-17 14:50:59.90328+03
418	1	1	users	2	UPDATE	{"is_active": false}	{"is_active": true}	{"source": "user_management"}	2026-01-17 14:51:05.370388+03
419	3	11	assets	40	CHECK_OUT	\N	{"status": "on_loan", "holder_name": "Hassan Security", "location_id": "12", "current_holder_id": "12"}	{"remarks": "", "location": "Admin Storage - Admin Building, Floor Ground, Room G08"}	2026-01-17 14:54:14.118859+03
420	1	1	maintenance_records	6	CREATE	\N	{"cost": 0, "status": "open", "asset_id": "12", "asset_name": "Brother HL-L8360", "maintenance_type": "preventive"}	{"start_date": "2026-01-19", "description": "Check Battery "}	2026-01-18 17:20:42.174987+03
421	\N	19	analytics	\N	EXPORT_ANALYTICS	\N	\N	{"total_assets": 29}	2026-01-19 16:17:15.061554+03
422	4	19	institutions	4	DEACTIVATE	{"is_active": true}	{"is_active": false}	{"name": "Catholic University "}	2026-01-22 15:17:36.989018+03
423	4	19	institutions	4	REACTIVATE	{"is_active": false}	{"is_active": true}	{"name": "Catholic University "}	2026-01-22 15:17:59.47146+03
424	\N	19	institution	4	UPDATE_INSTITUTION	\N	"{\\"name\\":\\"Catholic University \\",\\"code\\":\\"CUEA\\",\\"domain\\":\\"cuea.ac.ke\\",\\"phone_number\\":\\"+254700000005\\",\\"institution_type\\":\\"University\\",\\"address\\":\\"Bogani road 93104\\",\\"contact_email\\":\\"info@cuea.edu\\"}"	\N	2026-01-22 15:33:53.788396+03
425	\N	19	institution	1	UPDATE_INSTITUTION	\N	"{\\"name\\":\\"University of Nairobi \\",\\"code\\":\\"NUNI\\",\\"domain\\":\\"nuni.ac.ke\\",\\"phone_number\\":\\"+254700000001\\",\\"institution_type\\":\\"University\\",\\"address\\":\\"123 Main Street, Nairobi, Kenya\\",\\"contact_email\\":\\"admin@nuni.ac.ke\\"}"	\N	2026-01-22 15:39:16.692872+03
426	\N	19	institution	4	UPDATE_INSTITUTION	\N	"{\\"name\\":\\"Catholic University Of Eastern Africa\\",\\"code\\":\\"CUEA\\",\\"domain\\":\\"cuea.ac.ke\\",\\"phone_number\\":\\"+254700000005\\",\\"institution_type\\":\\"University\\",\\"address\\":\\"Bogani road 93104\\",\\"contact_email\\":\\"info@cuea.edu\\"}"	\N	2026-01-23 13:31:15.326739+03
427	\N	19	institution	1	UPDATE_INSTITUTION	\N	"{\\"name\\":\\"The University of Nairobi \\",\\"code\\":\\"NUNI\\",\\"domain\\":\\"nuni.ac.ke\\",\\"phone_number\\":\\"+254700000001\\",\\"institution_type\\":\\"University\\",\\"address\\":\\"123 Main Street, Nairobi, Kenya\\",\\"contact_email\\":\\"admin@nuni.ac.ke\\"}"	\N	2026-01-23 13:31:27.355465+03
428	1	1	maintenance_records	3	CLOSE	{"status": "in_progress"}	{"status": "closed", "closed_by": 1, "actual_cost": 2000}	{"asset_code": "NUNI-DES-003", "asset_name": "Lenovo ThinkCentre", "completion_notes": ""}	2026-01-23 14:11:19.867304+03
429	1	1	maintenance_records	4	CLOSE	{"status": "open"}	{"status": "closed", "closed_by": 1, "actual_cost": 0}	{"asset_code": "NUNI-DES-003", "asset_name": "Lenovo ThinkCentre", "completion_notes": ""}	2026-01-23 14:11:35.89676+03
430	1	1	maintenance_records	6	CLOSE	{"status": "open"}	{"status": "closed", "closed_by": 1, "actual_cost": 3500}	{"asset_code": "NUNI-PRT-002", "asset_name": "Brother HL-L8360", "completion_notes": ""}	2026-01-23 14:13:04.636142+03
431	1	1	users	3	UPDATE	{"email": "ict@nuni.ac.ke", "is_active": true, "last_name": "ICT", "first_name": "Ahmed"}	{"email": "ict@nuni.ac.ke", "is_active": true, "last_name": "ICT", "first_name": "Ahmed"}	{"source": "user_management"}	2026-01-23 14:46:09.238946+03
\.


--
-- TOC entry 5191 (class 0 OID 16802)
-- Dependencies: 250
-- Data for Name: checkout_requests; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.checkout_requests (id, institution_id, asset_id, requested_by, approved_by, status, reason, requested_at, decided_at) FROM stdin;
\.


--
-- TOC entry 5163 (class 0 OID 16407)
-- Dependencies: 222
-- Data for Name: departments; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.departments (id, institution_id, name, code, created_at) FROM stdin;
1	1	Computer Science	CS	2025-12-16 03:51:43.40089+03
2	1	Electrical Engineering	EE	2025-12-16 03:51:43.40089+03
3	1	Business Administration	BA	2025-12-16 03:51:43.40089+03
4	1	Library Services	LIB	2025-12-16 03:51:43.40089+03
5	2	Information Technology	IT	2025-12-16 03:51:43.40089+03
6	2	Civil Engineering	CE	2025-12-16 03:51:43.40089+03
7	2	Student Services	SS	2025-12-16 03:51:43.40089+03
8	3	Electronics	ELC	2025-12-16 03:51:43.40089+03
9	3	Mechanical Engineering	ME	2025-12-16 03:51:43.40089+03
10	3	Admin & Finance	AF	2025-12-16 03:51:43.40089+03
\.


--
-- TOC entry 5161 (class 0 OID 16390)
-- Dependencies: 220
-- Data for Name: institutions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.institutions (id, name, code, address, contact_email, created_at, updated_at, is_active, domain, phone_number, institution_type) FROM stdin;
2	Kampala Institute of Technology	KIT	456 Tech Avenue, Kampala, Uganda	admin@kit.ac.ug	2025-12-16 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	t	kit.ac.ug	+256700000002	University
5	Daystar University 	DSU	AthiRiver Machakos	info@daystar.edu	2026-01-17 12:14:27.283858+03	2026-01-17 12:14:27.283858+03	t	daystar.ac.ke	+254700000003	University
3	Dar es Salaam Polytechnic	DSP	789 Education Way, Dar es Salaam, Tanzania	admin@dsp.ac.tz	2025-12-16 03:51:43.40089+03	2026-01-17 13:11:17.110835+03	t	dsp.ac.tz	+255700000004	College
4	Catholic University Of Eastern Africa	CUEA	Bogani road 93104	info@cuea.edu	2026-01-16 18:44:43.993035+03	2026-01-23 13:31:15.312268+03	t	cuea.ac.ke	+254700000005	University
1	The University of Nairobi 	NUNI	123 Main Street, Nairobi, Kenya	admin@nuni.ac.ke	2025-12-16 03:51:43.40089+03	2026-01-23 13:31:27.348053+03	t	nuni.ac.ke	+254700000001	University
\.


--
-- TOC entry 5193 (class 0 OID 24994)
-- Dependencies: 252
-- Data for Name: locations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.locations (id, institution_id, name, building, floor, room, description, is_active, created_at, updated_at) FROM stdin;
1	1	CS Lab 1	Science Building	2	201	Computer Science Laboratory 1	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
2	1	CS Lab 2	Science Building	2	202	Computer Science Laboratory 2	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
3	1	EE Workshop	Engineering Building	1	105	Electrical Engineering Workshop	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
4	1	Library Storage	Library Building	Ground	G12	Library Equipment Storage	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
5	1	Admin Office	Administration Block	3	301	Administration Office	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
6	1	Server Room	ICT Building	Basement	B01	Main Server Room	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
7	2	IT Lab	Technology Block	1	101	IT Department Lab	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
8	2	Server Room	ICT Center	Ground	G05	Main Server Room	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
9	2	Civil Workshop	Engineering Block	1	110	Civil Engineering Workshop	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
10	3	Electronics Lab	Lab Building	2	205	Electronics Laboratory	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
11	3	Mechanical Workshop	Workshop Building	1	115	Mechanical Engineering Workshop	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
12	3	Admin Storage	Admin Building	Ground	G08	Administrative Storage	t	2026-01-14 14:45:12.27256+03	2026-01-14 14:45:12.27256+03
\.


--
-- TOC entry 5179 (class 0 OID 16629)
-- Dependencies: 238
-- Data for Name: maintenance_records; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.maintenance_records (id, institution_id, asset_id, reported_by, assigned_to, maintenance_type, description, status, estimated_cost, start_date, end_date, created_at, updated_at, actual_completion_date, actual_cost, completion_notes, closed_by, closed_at) FROM stdin;
1	1	11	6	3	corrective	Screen flickering issue	closed	150.00	2025-12-06 03:51:43.40089+03	2025-12-08 03:51:43.40089+03	2025-12-06 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	0.00	\N	\N	\N
2	1	4	6	3	preventive	Routine maintenance - cleaning	open	0.00	2025-12-14 03:51:43.40089+03	\N	2025-12-14 03:51:43.40089+03	2025-12-16 03:51:43.40089+03	\N	0.00	\N	\N	\N
5	2	13	9	9	corrective	Change RAM	open	300.00	2026-01-08 00:00:00+03	\N	2026-01-09 09:13:03.341619+03	2026-01-09 09:13:03.341619+03	\N	0.00	\N	\N	\N
3	1	10	6	3	corrective	Hard drive making noise	closed	200.00	2025-12-11 03:51:43.40089+03	\N	2025-12-11 03:51:43.40089+03	2026-01-23 14:11:19.867304+03	2026-01-23 14:11:19.867304+03	2000.00		1	2026-01-23 14:11:19.867304+03
4	1	10	1	3	preventive	Check storage usage 	closed	0.00	2026-01-15 00:00:00+03	\N	2026-01-08 17:55:33.637917+03	2026-01-23 14:11:35.89676+03	2026-01-23 14:11:35.89676+03	0.00		1	2026-01-23 14:11:35.89676+03
6	1	12	1	3	preventive	Check Battery 	closed	0.00	2026-01-19 00:00:00+03	\N	2026-01-18 17:20:42.174987+03	2026-01-23 14:13:04.636142+03	2026-01-23 14:13:04.636142+03	3500.00		1	2026-01-23 14:13:04.636142+03
\.


--
-- TOC entry 5183 (class 0 OID 16697)
-- Dependencies: 242
-- Data for Name: predictive_features; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.predictive_features (id, institution_id, asset_id, feature_date, usage_count, repairs_last_90d, avg_time_between_repairs, last_maintenance_date, asset_age_months, avg_maintenance_cost, additional_json, created_at) FROM stdin;
1	1	1	2025-12-16	5	0	\N	\N	12	0.00	\N	2025-12-16 03:51:43.40089+03
2	1	4	2025-12-16	8	1	45.00	2025-11-01	11	100.00	\N	2025-12-16 03:51:43.40089+03
3	1	10	2025-12-16	12	2	30.00	2025-12-01	11	150.00	\N	2025-12-16 03:51:43.40089+03
4	1	11	2025-12-16	20	3	20.00	2025-12-06	11	175.00	\N	2025-12-16 03:51:43.40089+03
5	2	14	2025-12-16	3	0	\N	\N	10	0.00	\N	2025-12-16 03:51:43.40089+03
22	1	22	2026-01-13	6	0	365.00	\N	0	0.00	\N	2026-01-13 22:51:56.470124+03
40	1	39	2026-01-13	0	0	365.00	\N	0	0.00	\N	2026-01-13 22:56:29.767831+03
125	1	6	2026-01-14	1	0	365.00	\N	30	0.00	\N	2026-01-14 12:39:04.515153+03
130	1	12	2026-01-14	8	0	365.00	\N	25	0.00	\N	2026-01-14 12:39:04.515153+03
113	1	1	2026-01-14	3	0	365.00	\N	35	0.00	\N	2026-01-14 12:39:04.515153+03
116	1	7	2026-01-14	1	0	365.00	\N	29	0.00	\N	2026-01-14 12:39:04.515153+03
149	2	16	2026-01-14	0	0	365.00	\N	21	0.00	\N	2026-01-14 17:25:59.296337+03
150	2	13	2026-01-14	0	1	365.00	\N	24	300.00	\N	2026-01-14 17:25:59.296337+03
151	2	14	2026-01-14	0	0	365.00	\N	22	0.00	\N	2026-01-14 17:25:59.296337+03
152	2	15	2026-01-14	0	0	365.00	\N	21	0.00	\N	2026-01-14 17:25:59.296337+03
18	1	4	2026-01-13	5	1	365.00	\N	33	0.00	\N	2026-01-13 22:51:56.470124+03
19	1	12	2026-01-13	8	0	365.00	\N	25	0.00	\N	2026-01-13 22:51:56.470124+03
121	1	33	2026-01-14	0	0	365.00	\N	0	0.00	\N	2026-01-14 12:39:04.515153+03
122	1	35	2026-01-14	0	0	365.00	\N	0	0.00	\N	2026-01-14 12:39:04.515153+03
123	1	23	2026-01-14	1	0	365.00	\N	0	0.00	\N	2026-01-14 12:39:04.515153+03
126	1	38	2026-01-14	0	0	365.00	\N	1	0.00	\N	2026-01-14 12:39:04.515153+03
127	1	22	2026-01-14	6	0	365.00	\N	0	0.00	\N	2026-01-14 12:39:04.515153+03
128	1	39	2026-01-14	0	0	365.00	\N	0	0.00	\N	2026-01-14 12:39:04.515153+03
114	1	2	2026-01-14	1	0	365.00	\N	34	0.00	\N	2026-01-14 12:39:04.515153+03
118	1	9	2026-01-14	0	0	365.00	\N	28	0.00	\N	2026-01-14 12:39:04.515153+03
115	1	3	2026-01-14	1	0	365.00	\N	34	0.00	\N	2026-01-14 12:39:04.515153+03
120	1	10	2026-01-14	1	2	365.00	\N	26	100.00	\N	2026-01-14 12:39:04.515153+03
129	1	4	2026-01-14	5	1	365.00	\N	33	0.00	\N	2026-01-14 12:39:04.515153+03
117	1	8	2026-01-14	0	0	365.00	\N	28	0.00	\N	2026-01-14 12:39:04.515153+03
119	1	11	2026-01-14	0	1	365.00	2025-12-08	26	150.00	\N	2026-01-14 12:39:04.515153+03
124	1	5	2026-01-14	3	0	365.00	\N	32	0.00	\N	2026-01-14 12:39:04.515153+03
6	1	1	2026-01-13	2	0	365.00	\N	35	0.00	\N	2026-01-13 22:51:56.470124+03
7	1	2	2026-01-13	1	0	365.00	\N	34	0.00	\N	2026-01-13 22:51:56.470124+03
8	1	3	2026-01-13	1	0	365.00	\N	34	0.00	\N	2026-01-13 22:51:56.470124+03
9	1	7	2026-01-13	0	0	365.00	\N	29	0.00	\N	2026-01-13 22:51:56.470124+03
10	1	8	2026-01-13	0	0	365.00	\N	28	0.00	\N	2026-01-13 22:51:56.470124+03
11	1	9	2026-01-13	0	0	365.00	\N	27	0.00	\N	2026-01-13 22:51:56.470124+03
12	1	11	2026-01-13	0	1	365.00	2025-12-08	26	150.00	\N	2026-01-13 22:51:56.470124+03
13	1	10	2026-01-13	1	2	365.00	\N	26	100.00	\N	2026-01-13 22:51:56.470124+03
14	1	33	2026-01-13	0	0	365.00	\N	0	0.00	\N	2026-01-13 22:51:56.470124+03
15	1	35	2026-01-13	0	0	365.00	\N	0	0.00	\N	2026-01-13 22:51:56.470124+03
16	1	23	2026-01-13	1	0	365.00	\N	0	0.00	\N	2026-01-13 22:51:56.470124+03
17	1	5	2026-01-13	3	0	365.00	\N	32	0.00	\N	2026-01-13 22:51:56.470124+03
20	1	6	2026-01-13	1	0	365.00	\N	30	0.00	\N	2026-01-13 22:51:56.470124+03
21	1	38	2026-01-13	0	0	365.00	\N	1	0.00	\N	2026-01-13 22:51:56.470124+03
153	1	33	2026-01-18	0	0	365.00	\N	0	0.00	\N	2026-01-18 16:58:57.577217+03
154	1	35	2026-01-18	0	0	365.00	\N	0	0.00	\N	2026-01-18 16:58:57.577217+03
155	1	23	2026-01-18	1	0	365.00	\N	0	0.00	\N	2026-01-18 16:58:57.577217+03
156	1	38	2026-01-18	0	0	365.00	\N	1	0.00	\N	2026-01-18 16:58:57.577217+03
157	1	22	2026-01-18	6	0	365.00	\N	0	0.00	\N	2026-01-18 16:58:57.577217+03
158	1	39	2026-01-18	0	0	365.00	\N	0	0.00	\N	2026-01-18 16:58:57.577217+03
159	1	2	2026-01-18	1	0	365.00	\N	34	0.00	\N	2026-01-18 16:58:57.577217+03
160	1	9	2026-01-18	0	0	365.00	\N	28	0.00	\N	2026-01-18 16:58:57.577217+03
161	1	3	2026-01-18	1	0	365.00	\N	34	0.00	\N	2026-01-18 16:58:57.577217+03
162	1	10	2026-01-18	1	2	365.00	\N	26	100.00	\N	2026-01-18 16:58:57.577217+03
163	1	4	2026-01-18	5	1	365.00	\N	33	0.00	\N	2026-01-18 16:58:57.577217+03
164	1	8	2026-01-18	0	0	365.00	\N	28	0.00	\N	2026-01-18 16:58:57.577217+03
165	1	11	2026-01-18	0	1	365.00	2025-12-08	26	150.00	\N	2026-01-18 16:58:57.577217+03
166	1	5	2026-01-18	3	0	365.00	\N	32	0.00	\N	2026-01-18 16:58:57.577217+03
167	1	6	2026-01-18	1	0	365.00	\N	31	0.00	\N	2026-01-18 16:58:57.577217+03
169	1	1	2026-01-18	3	0	365.00	\N	36	0.00	\N	2026-01-18 16:58:57.577217+03
168	1	12	2026-01-18	8	1	365.00	\N	25	0.00	\N	2026-01-18 16:58:57.577217+03
170	1	7	2026-01-18	1	0	365.00	\N	29	0.00	\N	2026-01-18 16:58:57.577217+03
189	1	33	2026-01-23	0	0	365.00	\N	0	0.00	\N	2026-01-23 13:50:44.443969+03
190	1	35	2026-01-23	0	0	365.00	\N	0	0.00	\N	2026-01-23 13:50:44.443969+03
191	1	23	2026-01-23	1	0	365.00	\N	0	0.00	\N	2026-01-23 13:50:44.443969+03
192	1	38	2026-01-23	0	0	365.00	\N	2	0.00	\N	2026-01-23 13:50:44.443969+03
193	1	22	2026-01-23	6	0	365.00	\N	0	0.00	\N	2026-01-23 13:50:44.443969+03
194	1	39	2026-01-23	0	0	365.00	\N	0	0.00	\N	2026-01-23 13:50:44.443969+03
195	1	2	2026-01-23	1	0	365.00	\N	35	0.00	\N	2026-01-23 13:50:44.443969+03
196	1	9	2026-01-23	0	0	365.00	\N	28	0.00	\N	2026-01-23 13:50:44.443969+03
197	1	3	2026-01-23	1	0	365.00	\N	34	0.00	\N	2026-01-23 13:50:44.443969+03
198	1	10	2026-01-23	1	2	365.00	\N	26	100.00	\N	2026-01-23 13:50:44.443969+03
199	1	4	2026-01-23	5	1	365.00	\N	33	0.00	\N	2026-01-23 13:50:44.443969+03
200	1	8	2026-01-23	0	0	365.00	\N	28	0.00	\N	2026-01-23 13:50:44.443969+03
201	1	11	2026-01-23	0	1	365.00	2025-12-08	26	150.00	\N	2026-01-23 13:50:44.443969+03
202	1	5	2026-01-23	3	0	365.00	\N	32	0.00	\N	2026-01-23 13:50:44.443969+03
203	1	6	2026-01-23	1	0	365.00	\N	31	0.00	\N	2026-01-23 13:50:44.443969+03
204	1	1	2026-01-23	3	0	365.00	\N	36	0.00	\N	2026-01-23 13:50:44.443969+03
205	1	7	2026-01-23	1	0	365.00	\N	30	0.00	\N	2026-01-23 13:50:44.443969+03
206	1	12	2026-01-23	8	1	365.00	\N	25	0.00	\N	2026-01-23 13:50:44.443969+03
\.


--
-- TOC entry 5165 (class 0 OID 16426)
-- Dependencies: 224
-- Data for Name: roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.roles (id, name, description) FROM stdin;
1	super_admin	System administrator with access to all institutions
2	admin	Institution administrator
3	security	Security team - manages checkout/checkin
4	ict	ICT team - manages asset registration and maintenance
5	auditor	Auditor - read-only access to audit logs and reports
6	manager	Department manager - can request checkouts for team
7	staff	Staff member - can view assigned assets
\.


--
-- TOC entry 5189 (class 0 OID 16776)
-- Dependencies: 248
-- Data for Name: system_settings; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.system_settings (id, institution_id, key, value) FROM stdin;
1	1	asset_code_prefix	"NUNI"
2	1	checkout_approval_required	true
3	1	maintenance_alert_threshold	0.7
4	2	asset_code_prefix	"KIT"
5	2	checkout_approval_required	true
6	3	asset_code_prefix	"DSP"
7	3	checkout_approval_required	false
8	1	global_statuses	[{"id": 1, "name": "available", "color": "#10b981", "description": "Asset is available for use"}, {"id": 2, "name": "on_loan", "color": "#3b82f6", "description": "Asset is checked out"}, {"id": 3, "name": "maintenance", "color": "#f59e0b", "description": "Asset is under maintenance"}, {"id": 4, "name": "retired", "color": "#ef4444", "description": "Asset is retired"}]
\.


--
-- TOC entry 5177 (class 0 OID 16577)
-- Dependencies: 236
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.transactions (id, institution_id, asset_id, transaction_type, from_user_id, to_user_id, from_department_id, to_department_id, from_location, to_location, remarks, performed_by, performed_at) FROM stdin;
1	1	1	check_out	5	6	1	1	\N	\N	Checked out for teaching	2	2025-12-11 03:51:43.40089+03
2	1	1	check_in	6	5	1	1	\N	\N	Returned in good condition	2	2025-12-13 03:51:43.40089+03
3	1	2	check_out	5	6	1	1	\N	\N	Lab use	2	2025-12-09 03:51:43.40089+03
4	1	3	transfer	\N	\N	1	2	\N	\N	Transferred to Electrical dept	3	2025-12-12 03:51:43.40089+03
5	1	4	check_out	5	6	2	2	\N	\N	Engineering lab	2	2025-12-14 03:51:43.40089+03
6	1	4	check_out	\N	6	2	2	\N	Room 201	For classroom use	1	2026-01-08 17:29:06.436607+03
7	1	4	check_in	6	\N	2	2	\N	\N	Returned in good condition 	1	2026-01-08 17:35:18.515959+03
8	1	5	transfer	\N	\N	1	3		Floor 5, room 8	To use in printing 	1	2026-01-08 17:42:16.573086+03
9	1	5	check_out	\N	1	3	3	\N	Room 313		1	2026-01-08 17:42:47.006786+03
10	1	10	check_out	\N	3	2	2	\N	room 32		1	2026-01-08 17:43:52.590573+03
11	3	19	check_out	\N	12	9	9	\N	Lab B	Check firewalls	11	2026-01-08 18:20:43.725696+03
12	1	12	check_out	\N	6	3	3	\N			4	2026-01-08 18:49:14.514825+03
13	1	12	check_in	6	\N	3	3	\N	\N		1	2026-01-08 22:38:39.848901+03
14	1	23	check_out	\N	3	\N	\N	\N	Room 31		1	2026-01-09 10:33:27.590286+03
15	1	5	check_in	1	\N	3	3	\N	\N		1	2026-01-10 01:19:30.77987+03
16	1	4	check_out	\N	16	2	2	\N	Room 01		1	2026-01-10 01:19:46.396451+03
17	1	22	transfer	\N	\N	\N	4		Library 	Research in Library 	5	2026-01-12 15:54:19.784638+03
18	1	22	check_out	\N	6	4	4	\N	Lec 039		1	2026-01-12 16:26:47.792249+03
19	1	22	check_in	6	\N	4	4	\N	\N		1	2026-01-12 16:27:13.44571+03
20	1	12	transfer	\N	\N	3	1		Floor 4		1	2026-01-12 20:39:42.699548+03
21	1	12	transfer	\N	\N	1	3				1	2026-01-12 20:40:33.566915+03
22	1	6	transfer	\N	\N	3	4				1	2026-01-12 20:54:16.627062+03
23	1	22	check_out	\N	5	4	4	\N	TH 07		1	2026-01-13 11:46:00.735736+03
24	1	22	check_in	5	\N	4	4	\N	\N		1	2026-01-13 11:47:49.982082+03
25	1	22	transfer	\N	\N	4	1		Lab 4		1	2026-01-13 11:48:46.438623+03
26	1	4	check_in	16	\N	2	2	\N	\N		1	2026-01-13 23:11:14.821113+03
27	1	12	check_out	\N	6	3	3	\N			1	2026-01-13 23:11:54.274624+03
28	1	12	check_in	6	\N	3	3	\N	\N		1	2026-01-13 23:12:03.480424+03
29	1	12	check_out	\N	16	3	3	\N			1	2026-01-13 23:12:12.433194+03
30	1	12	check_in	16	\N	3	3	\N	\N		1	2026-01-13 23:12:19.002433+03
31	1	1	check_out	\N	4	1	1	\N	Admin Office - Administration Block, Floor 3, Room 301		1	2026-01-14 14:58:04.51533+03
32	1	7	transfer	\N	\N	4	2		EE Workshop - Engineering Building, Floor 1, Room 105		1	2026-01-14 14:58:26.703825+03
33	3	40	check_out	\N	12	\N	\N	\N	Admin Storage - Admin Building, Floor Ground, Room G08		11	2026-01-17 14:54:14.118859+03
\.


--
-- TOC entry 5169 (class 0 OID 16460)
-- Dependencies: 228
-- Data for Name: user_roles; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.user_roles (id, user_id, role_id, institution_id, assigned_at) FROM stdin;
1	1	2	1	2025-12-16 03:51:43.40089+03
2	2	3	1	2025-12-16 03:51:43.40089+03
4	4	5	1	2025-12-16 03:51:43.40089+03
6	6	7	1	2025-12-16 03:51:43.40089+03
7	7	2	2	2025-12-16 03:51:43.40089+03
8	8	3	2	2025-12-16 03:51:43.40089+03
9	9	4	2	2025-12-16 03:51:43.40089+03
10	10	6	2	2025-12-16 03:51:43.40089+03
11	11	2	3	2025-12-16 03:51:43.40089+03
12	12	3	3	2025-12-16 03:51:43.40089+03
13	13	4	3	2025-12-16 03:51:43.40089+03
5	5	6	1	2025-12-16 03:51:43.40089+03
19	19	1	\N	2026-01-15 16:40:34.833918+03
22	22	2	4	2026-01-17 11:45:22.289675+03
17	16	6	1	2026-01-10 00:45:18.913116+03
3	3	4	1	2025-12-16 03:51:43.40089+03
\.


--
-- TOC entry 5167 (class 0 OID 16439)
-- Dependencies: 226
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, institution_id, username, email, password_hash, first_name, last_name, is_active, created_at, last_login, updated_at) FROM stdin;
8	2	security_kit	security@kit.ac.ug	$2b$10$YIjlrjkM5Z6K7pLkM9mK2OL4q5R6S7T8U9V0W1X2Y3Z4A5B6C7D8E	Joseph	Security	t	2025-12-16 03:51:43.40089+03	\N	2026-01-10 00:44:53.776651+03
10	2	manager_it	manager@it.kit.ac.ug	$2b$10$YIjlrjkM5Z6K7pLkM9mK2OL4q5R6S7T8U9V0W1X2Y3Z4A5B6C7D8E	Robert	Manager	t	2025-12-16 03:51:43.40089+03	\N	2026-01-10 00:44:53.776651+03
5	1	manager_cs	manager@cs.nuni.ac.ke	$2y$10$NKlb00x9JVllJH9nrYPH5uTRTVIznGJn07g5v40Dz4QaaWDpeaPGG	David	Manager	t	2025-12-16 03:51:43.40089+03	2026-01-12 16:08:03.736417+03	2026-01-17 14:50:59.899344+03
19	\N	super_admin	super@miams.system	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	System	Admin	t	2026-01-15 16:36:52.072059+03	2026-01-23 22:30:34.675439+03	2026-01-15 16:36:52.072059+03
2	1	security_nuni	security@nuni.ac.ke	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	Sarah	Security	t	2025-12-16 03:51:43.40089+03	2026-01-23 22:32:29.071022+03	2026-01-17 14:51:05.362184+03
23	2	ianmutiso	ianmutiso@kit.ac.ug	$2y$12$f4k6SYv9ulf8DJzIZ0cxWe/WMnTC/Bm2UzO2PJzp7iE/qXinDbREa	Ian	Mutiso	t	2026-01-17 16:14:03.298165+03	2026-01-17 16:46:43.850485+03	2026-01-17 16:14:03.298165+03
17	2	lewiskiplangat	simatwalewiss@gmail.com	$2y$12$ptoFG04QUxRZqdtBttOIM.8KJ1nAt6IEwCyy2t3Cn0cu.k8ArK7li	Lewis	Kiplangat	t	2026-01-09 00:08:23.144251+03	2026-01-09 00:08:31.409958+03	2026-01-10 00:44:53.776651+03
16	1	lewissimatwa	lewissimatwa@gmail.com	$2y$10$/hATmcemywAiu6/baE5Efu66AiPx5GdXBlcqk2pniY2h5CUDiKQR.	Lewis	Simatwa	t	2026-01-08 23:35:44.121648+03	2026-01-12 15:50:43.975611+03	2026-01-17 11:58:44.99837+03
13	3	ict_dsp	ict@dsp.ac.tz	$2b$10$YIjlrjkM5Z6K7pLkM9mK2OL4q5R6S7T8U9V0W1X2Y3Z4A5B6C7D8E	Zainab	ICT	t	2025-12-16 03:51:43.40089+03	\N	2026-01-17 14:08:36.858843+03
4	1	auditor_nuni	auditor@nuni.ac.ke	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	Grace	Auditor	t	2025-12-16 03:51:43.40089+03	2026-01-12 23:55:59.618354+03	2026-01-10 00:44:53.776651+03
22	4	admin_cuea	admin@cuea.ac.ke	$2y$12$8aqLxJb7bM4RssJPowCfS.Lch0PWQobm4XkScuJWa.AUMr0VwMvWe	Admin 	cuea	f	2026-01-17 11:45:22.289675+03	2026-01-23 13:32:18.269374+03	2026-01-17 11:45:22.289675+03
6	1	staff_nuni	staff@nuni.ac.ke	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	Mary	Staff	t	2025-12-16 03:51:43.40089+03	2026-01-09 10:37:52.268694+03	2026-01-10 00:44:53.776651+03
11	3	admin_dsp	admin@dsp.ac.tz	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	Amina	Admin	t	2025-12-16 03:51:43.40089+03	2026-01-17 15:43:34.583273+03	2026-01-10 00:44:53.776651+03
9	2	ict_kit	ict@kit.ac.ug	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	Patricia	ICT	t	2025-12-16 03:51:43.40089+03	2026-01-16 21:10:45.42034+03	2026-01-10 00:44:53.776651+03
1	1	admin_nuni	admin@nuni.ac.ke	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	John	Admin	t	2025-12-16 03:51:43.40089+03	2026-01-23 14:45:52.517028+03	2026-01-10 00:44:53.776651+03
12	3	security_dsp	security@dsp.ac.tz	$2b$10$YIjlrjkM5Z6K7pLkM9mK2OL4q5R6S7T8U9V0W1X2Y3Z4A5B6C7D8E	Hassan	Security	t	2025-12-16 03:51:43.40089+03	\N	2026-01-17 14:36:09.578308+03
3	1	ict_nuni	ict@nuni.ac.ke	$2y$10$6hv.X1kA3zIa1eGopqM.We2dVrIpIjF90kFB7XxUW81ZLsZtyzf9C	Ahmed	ICT	t	2025-12-16 03:51:43.40089+03	2026-01-23 14:46:16.66329+03	2026-01-23 14:46:09.238946+03
24	4	samathawangare	swangare@cuea.edu	$2y$12$NVaIDbFIEXDCeeyfoZzqzOTrU8LK7DPRdXLOIAfrU7E/NP8q7sRuW	Samatha	Wangare	f	2026-01-17 16:43:28.11736+03	2026-01-17 16:48:41.10092+03	2026-01-17 16:43:28.11736+03
7	2	admin_kit	admin@kit.ac.ug	$2y$12$L8Jvd8LhDmOtuPAafkBkGeQeN6Z0kMViF6qyeZsvDVupzcgl1Tgg2	Peter	Admin	t	2025-12-16 03:51:43.40089+03	2026-01-17 16:05:52.671213+03	2026-01-10 00:44:53.776651+03
\.


--
-- TOC entry 5232 (class 0 OID 0)
-- Dependencies: 239
-- Name: asset_documents_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_documents_id_seq', 4, true);


--
-- TOC entry 5233 (class 0 OID 0)
-- Dependencies: 253
-- Name: asset_images_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_images_id_seq', 1, false);


--
-- TOC entry 5234 (class 0 OID 0)
-- Dependencies: 243
-- Name: asset_risk_scores_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_risk_scores_id_seq', 206, true);


--
-- TOC entry 5235 (class 0 OID 0)
-- Dependencies: 233
-- Name: asset_tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_tags_id_seq', 19, true);


--
-- TOC entry 5236 (class 0 OID 0)
-- Dependencies: 229
-- Name: asset_types_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.asset_types_id_seq', 24, true);


--
-- TOC entry 5237 (class 0 OID 0)
-- Dependencies: 231
-- Name: assets_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.assets_id_seq', 43, true);


--
-- TOC entry 5238 (class 0 OID 0)
-- Dependencies: 245
-- Name: audit_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.audit_logs_id_seq', 431, true);


--
-- TOC entry 5239 (class 0 OID 0)
-- Dependencies: 249
-- Name: checkout_requests_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.checkout_requests_id_seq', 1, false);


--
-- TOC entry 5240 (class 0 OID 0)
-- Dependencies: 221
-- Name: departments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.departments_id_seq', 10, true);


--
-- TOC entry 5241 (class 0 OID 0)
-- Dependencies: 219
-- Name: institutions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.institutions_id_seq', 5, true);


--
-- TOC entry 5242 (class 0 OID 0)
-- Dependencies: 251
-- Name: locations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.locations_id_seq', 12, true);


--
-- TOC entry 5243 (class 0 OID 0)
-- Dependencies: 237
-- Name: maintenance_records_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.maintenance_records_id_seq', 6, true);


--
-- TOC entry 5244 (class 0 OID 0)
-- Dependencies: 241
-- Name: predictive_features_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.predictive_features_id_seq', 206, true);


--
-- TOC entry 5245 (class 0 OID 0)
-- Dependencies: 223
-- Name: roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.roles_id_seq', 7, true);


--
-- TOC entry 5246 (class 0 OID 0)
-- Dependencies: 247
-- Name: system_settings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.system_settings_id_seq', 8, true);


--
-- TOC entry 5247 (class 0 OID 0)
-- Dependencies: 235
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transactions_id_seq', 33, true);


--
-- TOC entry 5248 (class 0 OID 0)
-- Dependencies: 227
-- Name: user_roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_roles_id_seq', 22, true);


--
-- TOC entry 5249 (class 0 OID 0)
-- Dependencies: 225
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 24, true);


--
-- TOC entry 4936 (class 2606 OID 16679)
-- Name: asset_documents asset_documents_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents
    ADD CONSTRAINT asset_documents_pkey PRIMARY KEY (id);


--
-- TOC entry 4966 (class 2606 OID 25057)
-- Name: asset_images asset_images_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_images
    ADD CONSTRAINT asset_images_pkey PRIMARY KEY (id);


--
-- TOC entry 4944 (class 2606 OID 16737)
-- Name: asset_risk_scores asset_risk_scores_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_risk_scores
    ADD CONSTRAINT asset_risk_scores_pkey PRIMARY KEY (id);


--
-- TOC entry 4917 (class 2606 OID 16562)
-- Name: asset_tags asset_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_tags
    ADD CONSTRAINT asset_tags_pkey PRIMARY KEY (id);


--
-- TOC entry 4919 (class 2606 OID 16564)
-- Name: asset_tags asset_tags_tag_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_tags
    ADD CONSTRAINT asset_tags_tag_code_key UNIQUE (tag_code);


--
-- TOC entry 4902 (class 2606 OID 16499)
-- Name: asset_types asset_types_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_types
    ADD CONSTRAINT asset_types_pkey PRIMARY KEY (id);


--
-- TOC entry 4905 (class 2606 OID 16524)
-- Name: assets assets_institution_id_asset_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_institution_id_asset_code_key UNIQUE (institution_id, asset_code);


--
-- TOC entry 4907 (class 2606 OID 16522)
-- Name: assets assets_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_pkey PRIMARY KEY (id);


--
-- TOC entry 4947 (class 2606 OID 16761)
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- TOC entry 4957 (class 2606 OID 16815)
-- Name: checkout_requests checkout_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checkout_requests
    ADD CONSTRAINT checkout_requests_pkey PRIMARY KEY (id);


--
-- TOC entry 4887 (class 2606 OID 16418)
-- Name: departments departments_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_pkey PRIMARY KEY (id);


--
-- TOC entry 4879 (class 2606 OID 16405)
-- Name: institutions institutions_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.institutions
    ADD CONSTRAINT institutions_code_key UNIQUE (code);


--
-- TOC entry 4881 (class 2606 OID 25029)
-- Name: institutions institutions_domain_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.institutions
    ADD CONSTRAINT institutions_domain_unique UNIQUE (domain);


--
-- TOC entry 4883 (class 2606 OID 16403)
-- Name: institutions institutions_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.institutions
    ADD CONSTRAINT institutions_name_key UNIQUE (name);


--
-- TOC entry 4885 (class 2606 OID 16401)
-- Name: institutions institutions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.institutions
    ADD CONSTRAINT institutions_pkey PRIMARY KEY (id);


--
-- TOC entry 4962 (class 2606 OID 25009)
-- Name: locations locations_institution_id_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_institution_id_name_key UNIQUE (institution_id, name);


--
-- TOC entry 4964 (class 2606 OID 25007)
-- Name: locations locations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_pkey PRIMARY KEY (id);


--
-- TOC entry 4934 (class 2606 OID 16643)
-- Name: maintenance_records maintenance_records_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records
    ADD CONSTRAINT maintenance_records_pkey PRIMARY KEY (id);


--
-- TOC entry 4940 (class 2606 OID 16713)
-- Name: predictive_features predictive_features_asset_id_feature_date_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.predictive_features
    ADD CONSTRAINT predictive_features_asset_id_feature_date_key UNIQUE (asset_id, feature_date);


--
-- TOC entry 4942 (class 2606 OID 16711)
-- Name: predictive_features predictive_features_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.predictive_features
    ADD CONSTRAINT predictive_features_pkey PRIMARY KEY (id);


--
-- TOC entry 4890 (class 2606 OID 16437)
-- Name: roles roles_name_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_key UNIQUE (name);


--
-- TOC entry 4892 (class 2606 OID 16435)
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- TOC entry 4953 (class 2606 OID 16788)
-- Name: system_settings system_settings_institution_id_key_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_institution_id_key_key UNIQUE (institution_id, key);


--
-- TOC entry 4955 (class 2606 OID 16786)
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- TOC entry 4927 (class 2606 OID 16589)
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- TOC entry 4898 (class 2606 OID 16470)
-- Name: user_roles user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_pkey PRIMARY KEY (id);


--
-- TOC entry 4900 (class 2606 OID 16472)
-- Name: user_roles user_roles_user_id_role_id_institution_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_role_id_institution_id_key UNIQUE (user_id, role_id, institution_id);


--
-- TOC entry 4894 (class 2606 OID 16452)
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- TOC entry 4937 (class 1259 OID 16695)
-- Name: idx_asset_documents_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asset_documents_asset ON public.asset_documents USING btree (asset_id);


--
-- TOC entry 4967 (class 1259 OID 25073)
-- Name: idx_asset_images_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asset_images_asset ON public.asset_images USING btree (asset_id);


--
-- TOC entry 4968 (class 1259 OID 25074)
-- Name: idx_asset_images_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asset_images_institution ON public.asset_images USING btree (institution_id);


--
-- TOC entry 4945 (class 1259 OID 16748)
-- Name: idx_asset_risk_scores_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asset_risk_scores_asset ON public.asset_risk_scores USING btree (asset_id);


--
-- TOC entry 4920 (class 1259 OID 16575)
-- Name: idx_asset_tags_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asset_tags_asset ON public.asset_tags USING btree (asset_id);


--
-- TOC entry 4903 (class 1259 OID 16505)
-- Name: idx_asset_types_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_asset_types_institution ON public.asset_types USING btree (institution_id);


--
-- TOC entry 4908 (class 1259 OID 16840)
-- Name: idx_assets_date_retired; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_date_retired ON public.assets USING btree (date_retired) WHERE (date_retired IS NOT NULL);


--
-- TOC entry 4909 (class 1259 OID 16794)
-- Name: idx_assets_department; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_department ON public.assets USING btree (department_id);


--
-- TOC entry 4910 (class 1259 OID 16795)
-- Name: idx_assets_holder; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_holder ON public.assets USING btree (current_holder_id);


--
-- TOC entry 4911 (class 1259 OID 16545)
-- Name: idx_assets_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_institution ON public.assets USING btree (institution_id);


--
-- TOC entry 4912 (class 1259 OID 25021)
-- Name: idx_assets_location; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_location ON public.assets USING btree (location_id);


--
-- TOC entry 4913 (class 1259 OID 16839)
-- Name: idx_assets_retired; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_retired ON public.assets USING btree (status) WHERE (status = 'retired'::text);


--
-- TOC entry 4914 (class 1259 OID 16546)
-- Name: idx_assets_serial; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_serial ON public.assets USING btree (serial_number);


--
-- TOC entry 4915 (class 1259 OID 16547)
-- Name: idx_assets_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_assets_status ON public.assets USING btree (status);


--
-- TOC entry 4948 (class 1259 OID 16773)
-- Name: idx_audit_created_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_audit_created_at ON public.audit_logs USING btree (created_at);


--
-- TOC entry 4949 (class 1259 OID 16772)
-- Name: idx_audit_entity; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_audit_entity ON public.audit_logs USING btree (entity_type, entity_id);


--
-- TOC entry 4950 (class 1259 OID 16774)
-- Name: idx_audit_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_audit_institution ON public.audit_logs USING btree (institution_id);


--
-- TOC entry 4951 (class 1259 OID 16799)
-- Name: idx_audit_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_audit_user ON public.audit_logs USING btree (user_id);


--
-- TOC entry 4958 (class 1259 OID 16836)
-- Name: idx_checkout_requests_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_checkout_requests_asset ON public.checkout_requests USING btree (asset_id);


--
-- TOC entry 4959 (class 1259 OID 16837)
-- Name: idx_checkout_requests_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_checkout_requests_status ON public.checkout_requests USING btree (status);


--
-- TOC entry 4888 (class 1259 OID 16424)
-- Name: idx_departments_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_departments_institution ON public.departments USING btree (institution_id);


--
-- TOC entry 4876 (class 1259 OID 25026)
-- Name: idx_institutions_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_institutions_active ON public.institutions USING btree (is_active);


--
-- TOC entry 4877 (class 1259 OID 25030)
-- Name: idx_institutions_domain; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_institutions_domain ON public.institutions USING btree (domain);


--
-- TOC entry 4960 (class 1259 OID 25015)
-- Name: idx_locations_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_locations_institution ON public.locations USING btree (institution_id);


--
-- TOC entry 4928 (class 1259 OID 25039)
-- Name: idx_maintenance_actual_completion; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maintenance_actual_completion ON public.maintenance_records USING btree (actual_completion_date);


--
-- TOC entry 4929 (class 1259 OID 16664)
-- Name: idx_maintenance_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maintenance_asset ON public.maintenance_records USING btree (asset_id);


--
-- TOC entry 4930 (class 1259 OID 16798)
-- Name: idx_maintenance_assigned_to; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maintenance_assigned_to ON public.maintenance_records USING btree (assigned_to);


--
-- TOC entry 4931 (class 1259 OID 25038)
-- Name: idx_maintenance_closed_by; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maintenance_closed_by ON public.maintenance_records USING btree (closed_by);


--
-- TOC entry 4932 (class 1259 OID 16665)
-- Name: idx_maintenance_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_maintenance_status ON public.maintenance_records USING btree (status);


--
-- TOC entry 4938 (class 1259 OID 16724)
-- Name: idx_predictive_features_asset_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_predictive_features_asset_date ON public.predictive_features USING btree (asset_id, feature_date);


--
-- TOC entry 4921 (class 1259 OID 16625)
-- Name: idx_transactions_asset; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_asset ON public.transactions USING btree (asset_id);


--
-- TOC entry 4922 (class 1259 OID 16796)
-- Name: idx_transactions_from_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_from_user ON public.transactions USING btree (from_user_id);


--
-- TOC entry 4923 (class 1259 OID 16626)
-- Name: idx_transactions_institution; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_institution ON public.transactions USING btree (institution_id);


--
-- TOC entry 4924 (class 1259 OID 16627)
-- Name: idx_transactions_performed_at; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_performed_at ON public.transactions USING btree (performed_at);


--
-- TOC entry 4925 (class 1259 OID 16797)
-- Name: idx_transactions_to_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_to_user ON public.transactions USING btree (to_user_id);


--
-- TOC entry 4896 (class 1259 OID 16488)
-- Name: idx_user_roles_user; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_user_roles_user ON public.user_roles USING btree (user_id);


--
-- TOC entry 4895 (class 1259 OID 16458)
-- Name: ux_users_institution_username; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX ux_users_institution_username ON public.users USING btree (institution_id, username);


--
-- TOC entry 5012 (class 2620 OID 25023)
-- Name: user_roles trg_user_roles_scope; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_user_roles_scope BEFORE INSERT OR UPDATE ON public.user_roles FOR EACH ROW EXECUTE FUNCTION public.enforce_user_role_scope();


--
-- TOC entry 4994 (class 2606 OID 16685)
-- Name: asset_documents asset_documents_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents
    ADD CONSTRAINT asset_documents_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 4995 (class 2606 OID 16680)
-- Name: asset_documents asset_documents_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents
    ADD CONSTRAINT asset_documents_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4996 (class 2606 OID 16690)
-- Name: asset_documents asset_documents_uploaded_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_documents
    ADD CONSTRAINT asset_documents_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.users(id);


--
-- TOC entry 5009 (class 2606 OID 25058)
-- Name: asset_images asset_images_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_images
    ADD CONSTRAINT asset_images_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 5010 (class 2606 OID 25063)
-- Name: asset_images asset_images_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_images
    ADD CONSTRAINT asset_images_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 5011 (class 2606 OID 25068)
-- Name: asset_images asset_images_uploaded_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_images
    ADD CONSTRAINT asset_images_uploaded_by_fkey FOREIGN KEY (uploaded_by) REFERENCES public.users(id);


--
-- TOC entry 4999 (class 2606 OID 16743)
-- Name: asset_risk_scores asset_risk_scores_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_risk_scores
    ADD CONSTRAINT asset_risk_scores_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 5000 (class 2606 OID 16738)
-- Name: asset_risk_scores asset_risk_scores_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_risk_scores
    ADD CONSTRAINT asset_risk_scores_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4980 (class 2606 OID 16570)
-- Name: asset_tags asset_tags_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_tags
    ADD CONSTRAINT asset_tags_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 4981 (class 2606 OID 16565)
-- Name: asset_tags asset_tags_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_tags
    ADD CONSTRAINT asset_tags_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4974 (class 2606 OID 16500)
-- Name: asset_types asset_types_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.asset_types
    ADD CONSTRAINT asset_types_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE SET NULL;


--
-- TOC entry 4975 (class 2606 OID 16535)
-- Name: assets assets_asset_type_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_asset_type_id_fkey FOREIGN KEY (asset_type_id) REFERENCES public.asset_types(id) ON DELETE SET NULL;


--
-- TOC entry 4976 (class 2606 OID 16540)
-- Name: assets assets_current_holder_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_current_holder_id_fkey FOREIGN KEY (current_holder_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- TOC entry 4977 (class 2606 OID 16530)
-- Name: assets assets_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_department_id_fkey FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE SET NULL;


--
-- TOC entry 4978 (class 2606 OID 16525)
-- Name: assets assets_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4979 (class 2606 OID 25016)
-- Name: assets assets_location_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.assets
    ADD CONSTRAINT assets_location_id_fkey FOREIGN KEY (location_id) REFERENCES public.locations(id) ON DELETE SET NULL;


--
-- TOC entry 5001 (class 2606 OID 16762)
-- Name: audit_logs audit_logs_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id);


--
-- TOC entry 5002 (class 2606 OID 16767)
-- Name: audit_logs audit_logs_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id);


--
-- TOC entry 5004 (class 2606 OID 16831)
-- Name: checkout_requests checkout_requests_approved_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checkout_requests
    ADD CONSTRAINT checkout_requests_approved_by_fkey FOREIGN KEY (approved_by) REFERENCES public.users(id);


--
-- TOC entry 5005 (class 2606 OID 16821)
-- Name: checkout_requests checkout_requests_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checkout_requests
    ADD CONSTRAINT checkout_requests_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id);


--
-- TOC entry 5006 (class 2606 OID 16816)
-- Name: checkout_requests checkout_requests_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checkout_requests
    ADD CONSTRAINT checkout_requests_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id);


--
-- TOC entry 5007 (class 2606 OID 16826)
-- Name: checkout_requests checkout_requests_requested_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.checkout_requests
    ADD CONSTRAINT checkout_requests_requested_by_fkey FOREIGN KEY (requested_by) REFERENCES public.users(id);


--
-- TOC entry 4969 (class 2606 OID 16419)
-- Name: departments departments_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 5008 (class 2606 OID 25010)
-- Name: locations locations_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.locations
    ADD CONSTRAINT locations_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4989 (class 2606 OID 16649)
-- Name: maintenance_records maintenance_records_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records
    ADD CONSTRAINT maintenance_records_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 4990 (class 2606 OID 16659)
-- Name: maintenance_records maintenance_records_assigned_to_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records
    ADD CONSTRAINT maintenance_records_assigned_to_fkey FOREIGN KEY (assigned_to) REFERENCES public.users(id);


--
-- TOC entry 4991 (class 2606 OID 25033)
-- Name: maintenance_records maintenance_records_closed_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records
    ADD CONSTRAINT maintenance_records_closed_by_fkey FOREIGN KEY (closed_by) REFERENCES public.users(id);


--
-- TOC entry 4992 (class 2606 OID 16644)
-- Name: maintenance_records maintenance_records_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records
    ADD CONSTRAINT maintenance_records_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4993 (class 2606 OID 16654)
-- Name: maintenance_records maintenance_records_reported_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.maintenance_records
    ADD CONSTRAINT maintenance_records_reported_by_fkey FOREIGN KEY (reported_by) REFERENCES public.users(id);


--
-- TOC entry 4997 (class 2606 OID 16719)
-- Name: predictive_features predictive_features_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.predictive_features
    ADD CONSTRAINT predictive_features_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 4998 (class 2606 OID 16714)
-- Name: predictive_features predictive_features_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.predictive_features
    ADD CONSTRAINT predictive_features_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 5003 (class 2606 OID 16789)
-- Name: system_settings system_settings_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4982 (class 2606 OID 16595)
-- Name: transactions transactions_asset_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_asset_id_fkey FOREIGN KEY (asset_id) REFERENCES public.assets(id) ON DELETE CASCADE;


--
-- TOC entry 4983 (class 2606 OID 16610)
-- Name: transactions transactions_from_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_from_department_id_fkey FOREIGN KEY (from_department_id) REFERENCES public.departments(id);


--
-- TOC entry 4984 (class 2606 OID 16600)
-- Name: transactions transactions_from_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_from_user_id_fkey FOREIGN KEY (from_user_id) REFERENCES public.users(id);


--
-- TOC entry 4985 (class 2606 OID 16590)
-- Name: transactions transactions_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4986 (class 2606 OID 16620)
-- Name: transactions transactions_performed_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_performed_by_fkey FOREIGN KEY (performed_by) REFERENCES public.users(id);


--
-- TOC entry 4987 (class 2606 OID 16615)
-- Name: transactions transactions_to_department_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_to_department_id_fkey FOREIGN KEY (to_department_id) REFERENCES public.departments(id);


--
-- TOC entry 4988 (class 2606 OID 16605)
-- Name: transactions transactions_to_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_to_user_id_fkey FOREIGN KEY (to_user_id) REFERENCES public.users(id);


--
-- TOC entry 4971 (class 2606 OID 16483)
-- Name: user_roles user_roles_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


--
-- TOC entry 4972 (class 2606 OID 16478)
-- Name: user_roles user_roles_role_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_role_id_fkey FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE RESTRICT;


--
-- TOC entry 4973 (class 2606 OID 16473)
-- Name: user_roles user_roles_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.user_roles
    ADD CONSTRAINT user_roles_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- TOC entry 4970 (class 2606 OID 16453)
-- Name: users users_institution_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_institution_id_fkey FOREIGN KEY (institution_id) REFERENCES public.institutions(id) ON DELETE CASCADE;


-- Completed on 2026-01-24 21:47:57

--
-- PostgreSQL database dump complete
--

\unrestrict zqK8BIek9nm7uN39KbtcpSue5eYB4XBivNTuwcrotVTszfCEyz1xbMVF5lmeGTx

