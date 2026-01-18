export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    zipcode?: string;
    address?: string;
}

export interface CartItem {
    name: string;
    price: number;
    code: string;
    img: string;
    quantity: number;
}

export interface Product {
    id: number;
    name: string;
    code: string;
    price: number;
    img: string;
    active: boolean;
}

export interface OrderItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    product: Pick<Product, 'id' | 'name' | 'img' | 'code'>;
}

export interface Order {
    id: number;
    user_id: number;
    payment_method: 'cash_on_delivery' | 'stripe';
    total_price: number;
    stripe_status?: string;
    created_at: string;
    items: OrderItem[];
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
    is_active: boolean;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
