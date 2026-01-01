import axios from "axios";
import React, { useCallback, useState } from "react";

interface UseZipcodeSearchProps {
    onAddressFound: (address: string) => void;
    onZipcodeChange?: (zipcode: string) => void;
}

export function useZipcodeSearch({ onAddressFound, onZipcodeChange }: UseZipcodeSearchProps) {
    const [error, setError] = useState<string>('');
    const [isSearching, setIsSearching] = useState(false);

    const handleZipcodeChange = useCallback(async (e: React.ChangeEvent<HTMLInputElement>) => {
        const zipcode = e.target.value;
        onZipcodeChange?.(zipcode);
        setError('');

        if (/^\d{7}$/.test(zipcode)) {
            setIsSearching(true);
            try {
                const response = await axios.get('/api/zipcode/search', {
                    params: { zipcode },
                });

                if (response.data.results?.length > 0) {
                    const result = response.data.results[0];
                    onAddressFound(`${result.address1}${result.address2}${result.address3}`);
                } else {
                    setError('住所が見つかりませんでした。');
                }
            } catch (error) {
                console.error('郵便番号検索に失敗しました。', error);
                if (axios.isAxiosError(error) && error.response?.data.message) {
                    setError(error.response.data.message);
                } else {
                    setError('郵便番号検索に失敗しました');
                }
            } finally {
                setIsSearching(false);
            }
        }
    }, [onAddressFound, onZipcodeChange]);

    return { handleZipcodeChange, error, isSearching };
}
