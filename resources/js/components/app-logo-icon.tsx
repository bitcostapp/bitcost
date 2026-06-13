import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <rect width="24" height="24" rx="6" fill="#2563eb" />
            <rect x="5.5" y="12.5" width="2.6" height="5.5" rx="1.3" fill="#fff" />
            <rect x="10.7" y="9.5" width="2.6" height="8.5" rx="1.3" fill="#fff" />
            <rect x="15.9" y="6.5" width="2.6" height="11.5" rx="1.3" fill="#fff" />
        </svg>
    );
}
