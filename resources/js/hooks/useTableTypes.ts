import { useState, useEffect } from 'react';
import axios from 'axios';

export interface TableType {
    id: number | null;
    name: string;
    created_at: string | null;
    updated_at: string | null;
}

export default function useTableTypes() {
    const [tableTypes, setTableTypes] = useState<TableType[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        fetchTableTypes();
    }, []);

    const fetchTableTypes = async () => {
        try {
            setLoading(true);
            setError(null);

            const response = await axios.get('/table-types');
            setTableTypes(response.data);
        } catch (err: any) {
            console.error('Error fetching table types:', err);
            setError(err.response?.data?.error || 'حدث خطأ أثناء جلب أنواع الطاولات');

            // Set default option if fetch fails
            setTableTypes([
                {
                    id: null,
                    name: 'صالة',
                    created_at: null,
                    updated_at: null,
                }
            ]);
        } finally {
            setLoading(false);
        }
    };

    return {
        tableTypes,
        loading,
        error,
        refetch: fetchTableTypes,
    };
}
