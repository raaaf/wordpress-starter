// WordPress Global Types
declare global {
  interface Window {
    wp: any;
    Alpine: import('alpinejs').Alpine;
  }

  // ACF Types
  function get_field(field: string, postId?: number | string | false): any;
  function the_field(field: string, postId?: number | string | false): void;
  function have_rows(field: string, postId?: number | string | false): boolean;
  function the_row(): void;
  function get_sub_field(field: string): any;
  function the_sub_field(field: string): void;
  function get_field_object(field: string, postId?: number | string | false): any;
  
  // WordPress Functions
  function __(text: string, domain?: string): string;
  function _e(text: string, domain?: string): void;
  function _x(text: string, context: string, domain?: string): string;
  function esc_html(text: string): string;
  function esc_attr(text: string): string;
  function esc_url(url: string): string;
  function wp_nonce_field(action: string, name?: string, referer?: boolean, echo?: boolean): string;
  function wp_verify_nonce(nonce: string, action: string): boolean | number;
  
  // WordPress Variables
  const ajaxurl: string;
  
  interface WP_REST_Response {
    data: any;
    headers: Headers;
    status: number;
  }
}

// ACF Block Type
export interface ACFBlock {
  id: string;
  name: string;
  title: string;
  description: string;
  category: string;
  icon: string;
  keywords: string[];
  post_types?: string[];
  mode?: 'preview' | 'edit' | 'auto';
  align?: string;
  anchor?: string;
  className?: string;
  jsx?: boolean;
  supports?: {
    align?: boolean | string[];
    mode?: boolean;
    multiple?: boolean;
    jsx?: boolean;
    anchor?: boolean;
    customClassName?: boolean;
  };
}

// ACF Field Types
export interface ACFField<T = any> {
  key: string;
  label: string;
  name: string;
  type: string;
  value: T;
  required?: boolean;
  conditional_logic?: any;
  wrapper?: {
    width?: string;
    class?: string;
    id?: string;
  };
}

export interface ACFImageField {
  ID: number;
  id: number;
  title: string;
  filename: string;
  url: string;
  alt: string;
  description: string;
  caption: string;
  mime_type: string;
  type: string;
  width: number;
  height: number;
  sizes: {
    [key: string]: string;
  };
}

export interface ACFLinkField {
  title: string;
  url: string;
  target: string;
}

export interface ACFRepeaterField<T = any> extends Array<T> {}

export interface ACFFlexibleContentLayout<T = any> {
  acf_fc_layout: string;
  [key: string]: T;
}

export {};