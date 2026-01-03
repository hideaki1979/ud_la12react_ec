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

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
