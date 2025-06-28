<div class="text-lg font-semibold uppercase max-w-8xl px-0 lg:px-20 2xl:px-0 overflow-x-hidden">
    <div
        class="text-softWhite pt-3 pb-[14px] h-12"
        x-data="{ duration: '20s' }"
        x-init="
            const updateDuration = () => {
                const marqueeWidth = $refs.marquee.scrollWidth / 2;
                duration = `${marqueeWidth / 50}s`;
            };

            updateDuration();
            window.addEventListener('resize', updateDuration);

            return () => window.removeEventListener('resize', updateDuration);
        "
    >
        <div x-ref="marquee" class="flex whitespace-nowrap" :style="{ '--duration': duration, animation: 'marquee var(--duration) linear infinite' }" style="--duration: 38.82s; animation: marquee var(--duration) linear infinite;">
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
            <div class="flex items-center h-5 font-mono">
                <span class="mx-2 text-[#616161]">US</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2 font-bold">JULY 29-30</span>
                <span class="mx-2 text-[#616161]">2025</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 4 5" fill="none" class="mx-2">
                    <rect x="0.5" y="1" width="8" height="8" rx="0.25" fill="#FF281C"></rect>
                </svg>
                <span class="mx-2">DENVER, CO</span>
            </div>
        </div>

        <style>
            @keyframes marquee {
                0% {
                    transform: translateX(0);
                }
                100% {
                    transform: translateX(-50%);
                }
            }
        </style>
    </div>
</div>
