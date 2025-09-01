import React from 'react';
import { usePage } from '@inertiajs/react';
import { User } from '@/types';

interface IsAdminProps {
    children: React.ReactNode;
}

export default function IsAdmin({ children }: IsAdminProps) {
    const { auth } = usePage().props;
    const user = auth.user as User;

    if (user.role !== 'admin') {
        return null;
    }

    return <>{children}</>;
}
