        {{-- ── Trust bar ───────────────────────────────────────────────────────── --}}
        {{-- Frame 1984079385: 80px total (2×40px rows), 2px green border, 8px radius --}}
        <section
            class="max-w-7xl mx-auto flex flex-col rounded-lg overflow-hidden border-2 border-[#2AAF2F] mt-8 mb-6 hidden lg:block">

            {{-- Frame 1984079383: top green row, 40px, rounded top corners --}}
            <div class="h-10 bg-[#2AAF2F] rounded-t-lg flex items-center justify-between px-5">

                {{-- Frame 1984079386: left label group, gap 6px --}}
                <div class="flex items-center gap-1.5">
                    {{-- vuesax/linear/security-time icon 20×20 --}}
                    <svg width="20" height="20" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 shrink-0">
                        <path
                            d="M10.4167 18.2292L11.1804 17.8992C12.7818 17.212 14.1646 16.0997 15.1791 14.6829C16.1936 13.2661 16.8011 11.5986 16.9358 9.86125L17.3092 5.06959C17.3153 4.87115 17.253 4.67664 17.1329 4.51858C17.0128 4.36052 16.842 4.2485 16.6492 4.20126L15.3817 3.86949C14.022 3.51361 12.7152 2.98014 11.495 2.28285L10.4167 1.66667C8.77747 2.75947 6.9305 3.55351 5.00777 3.98279L4.18417 4.16667C3.99141 4.21391 3.82071 4.32586 3.70058 4.48383C3.58045 4.64181 3.51818 4.83622 3.52417 5.03459L3.8975 9.82625C4.03212 11.5637 4.63959 13.2312 5.6541 14.6481C6.6686 16.0651 8.05148 17.1774 9.65292 17.8646L10.4167 18.2292Z"
                            fill="white" />
                        <path d="M7.8125 9.54875L9.54875 11.2846L13.0208 7.8125" stroke="#2AAF2F" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    {{-- Md-18, weight 500 --}}
                    <span
                        class="text-white text-[18px] font-medium leading-[23px] font-['Outfit']">{{ __('Why choose :store?', ['store' => $storeName]) }}</span>
                </div>

                <div class="flex items-center gap-[18px]">

                    {{-- Frame 1984079387: Secure privacy, border-right #C4C4C4, px 8px --}}
                    <div class="flex items-center gap-1.5 px-2 border-r border-[#C4C4C4]">
                        <svg width="21" height="20" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 shrink-0">
                            <path
                                d="M11.7798 11.585C11.7798 10.821 11.1295 10.2016 10.3273 10.2016C9.52509 10.2016 8.87478 10.821 8.87478 11.585C8.87358 11.9089 8.9947 12.2224 9.21603 12.4683C9.43905 12.6896 9.51636 13.0098 9.41728 13.3016L9.23353 13.8183C9.16247 14.0507 9.20945 14.3013 9.36053 14.4959C9.51161 14.6904 9.74946 14.8066 10.0035 14.81H10.651C10.9135 14.8099 11.1599 14.6892 11.3126 14.4859C11.4653 14.2826 11.5057 14.0216 11.421 13.785L11.2023 13.2683C11.1032 12.9764 11.1805 12.6563 11.4035 12.435C11.6299 12.2025 11.7631 11.9016 11.7798 11.585Z"
                                fill="white" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M14.4835 6.76831V4.16831C14.484 3.48908 14.1943 2.83889 13.681 2.36733C13.1677 1.89576 12.475 1.6434 11.7623 1.66831H8.89229C8.17957 1.6434 7.48689 1.89576 6.97359 2.36733C6.46029 2.83889 6.17056 3.48908 6.17104 4.16831V6.76831C4.35849 7.37609 3.25616 9.12581 3.54604 10.935L4.37729 15.7433C4.78728 17.6587 6.5778 19.0201 8.62979 18.9766H12.0248C14.0914 19.026 15.8948 17.65 16.2948 15.7183L17.126 10.91C17.4002 9.1049 16.2921 7.36812 14.4835 6.76831ZM7.48353 4.16831C7.51666 3.458 8.14631 2.90675 8.89228 2.93498H11.7623C12.5083 2.90675 13.1379 3.458 13.171 4.16831V6.47664H7.48353V4.16831ZM14.7023 15.4683L15.5773 10.6433C15.6641 10.0191 15.4558 9.39035 15.0085 8.92664C14.4646 8.33919 13.6783 8.00739 12.856 8.01831H7.79853C6.979 8.00318 6.19319 8.3287 5.64603 8.90998C5.20943 9.38482 5.01706 10.0199 5.12103 10.6433L5.95228 15.4683C6.22358 16.6569 7.35377 17.4871 8.62978 17.435H12.0248C13.3008 17.4871 14.431 16.6569 14.7023 15.4683Z"
                                fill="white" />
                        </svg>
                        <span
                            class="text-white text-[14px] font-normal leading-[25px] tracking-[0.5px] font-['Outfit'] whitespace-nowrap">{{ __('Secure Privacy') }}</span>
                    </div>

                    {{-- Frame 1984079388: Safe payments, border-right #C4C4C4, pr 8px --}}
                    <div class="flex items-center gap-1.5 pr-2 border-r border-[#C4C4C4]">
                        <svg width="20" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 shrink-0">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M14.9999 4.37494H4.99993C3.15898 4.37494 1.6666 5.94194 1.6666 7.87494V13.1249C1.6666 15.0579 3.15898 16.6249 4.99993 16.6249H14.9999C16.8409 16.6249 18.3333 15.0579 18.3333 13.1249V7.87494C18.3333 5.94194 16.8409 4.37494 14.9999 4.37494ZM4.99993 5.68744H14.9999C15.5525 5.68744 16.0824 5.91791 16.4731 6.32814C16.8638 6.73838 17.0833 7.29478 17.0833 7.87494V11.5937H2.9166V7.87494C2.9166 6.66682 3.84934 5.68744 4.99993 5.68744ZM4.99993 15.3124H14.9999C15.5525 15.3124 16.0824 15.082 16.4731 14.6717C16.8638 14.2615 17.0833 13.7051 17.0833 13.1249V12.9062H2.9166V13.1249C2.9166 14.3331 3.84934 15.3124 4.99993 15.3124Z"
                                fill="white" />
                        </svg>
                        <span
                            class="text-white text-[14px] font-normal leading-[25px] tracking-[0.5px] font-['Outfit'] whitespace-nowrap">{{ __('Safe payments') }}</span>
                    </div>

                    {{-- Frame 1984079389: Delivery guarantee, no border --}}
                    <a href="#" class="flex items-center gap-1.5 text-white hover:underline">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 shrink-0">
                            <path
                                d="M12.5 1.66667V10C12.5 10.9167 11.75 11.6667 10.8334 11.6667H1.66669V6.35C2.27502 7.075 3.20838 7.525 4.24171 7.5C5.08338 7.48334 5.84169 7.15834 6.40836 6.61667C6.66669 6.40001 6.88337 6.125 7.05004 5.825C7.35004 5.31666 7.51669 4.71665 7.50002 4.09165C7.47502 3.11665 7.0417 2.25834 6.3667 1.66667H12.5Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M18.3334 11.6667V14.1667C18.3334 15.55 17.2167 16.6667 15.8334 16.6667H15C15 15.75 14.25 15 13.3334 15C12.4167 15 11.6667 15.75 11.6667 16.6667H8.33335C8.33335 15.75 7.58335 15 6.66669 15C5.75002 15 5.00002 15.75 5.00002 16.6667H4.16669C2.78335 16.6667 1.66669 15.55 1.66669 14.1667V11.6667H10.8334C11.75 11.6667 12.5 10.9167 12.5 10V4.16667H14.0334C14.6334 4.16667 15.1834 4.49168 15.4834 5.00835L16.9083 7.50001H15.8334C15.375 7.50001 15 7.875 15 8.33334V10.8333C15 11.2917 15.375 11.6667 15.8334 11.6667H18.3334Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M6.66667 18.3333C7.58714 18.3333 8.33333 17.5871 8.33333 16.6667C8.33333 15.7462 7.58714 15 6.66667 15C5.74619 15 5 15.7462 5 16.6667C5 17.5871 5.74619 18.3333 6.66667 18.3333Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M13.3334 18.3333C14.2538 18.3333 15 17.5871 15 16.6667C15 15.7462 14.2538 15 13.3334 15C12.4129 15 11.6667 15.7462 11.6667 16.6667C11.6667 17.5871 12.4129 18.3333 13.3334 18.3333Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M18.3333 10V11.6667H15.8333C15.375 11.6667 15 11.2917 15 10.8333V8.33333C15 7.875 15.375 7.5 15.8333 7.5H16.9083L18.3333 10Z"
                                stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path
                                d="M7.50002 4.09166C7.51669 4.71666 7.35004 5.31667 7.05004 5.825C6.88337 6.125 6.66669 6.40001 6.40836 6.61668C5.84169 7.15835 5.08338 7.48335 4.24171 7.50001C3.20838 7.52501 2.27502 7.07501 1.66669 6.35001C1.55002 6.22501 1.45002 6.08335 1.35836 5.94168C1.03336 5.45002 0.850021 4.8667 0.833354 4.2417C0.808354 3.1917 1.27501 2.23334 2.02501 1.60834C2.59168 1.14167 3.30833 0.850011 4.09166 0.833345C4.96666 0.816678 5.7667 1.13334 6.3667 1.66668C7.0417 2.25834 7.47502 3.11666 7.50002 4.09166Z"
                                stroke="white" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M2.8667 4.19169L3.70837 4.99165L5.45001 3.3083" stroke="white" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span
                            class="text-[14px] font-normal leading-[25px] tracking-[0.5px] font-['Outfit'] whitespace-nowrap">{{ __('Delivery Guarantee') }}</span>
                        {{-- vuesax/linear/arrow-right 16×16 --}}
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white"
                            class="w-4 h-4 shrink-0">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- "down" row (Frame 1984079384): 40px, white bg --}}
            <div class="h-10 bg-white flex items-center justify-between px-4">

                {{-- Frame 1984079392: notification icon + security text, gap 6px --}}
                <div class="flex items-center gap-1.5">
                    {{-- vuesax/linear/notification 24×24, green --}}
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6 shrink-0">
                        <path
                            d="M12.02 2.91C8.71 2.91 6.02 5.6 6.02 8.91V11.8C6.02 12.41 5.76 13.34 5.45 13.86L4.3 15.77C3.59 16.95 4.08 18.26 5.38 18.7C9.69 20.14 14.34 20.14 18.65 18.7C19.86 18.3 20.39 16.87 19.73 15.77L18.58 13.86C18.28 13.34 18.02 12.41 18.02 11.8V8.91C18.02 5.61 15.32 2.91 12.02 2.91Z"
                            fill="#2AAF2F" stroke="#2AAF2F" stroke-width="1.5" stroke-miterlimit="10"
                            stroke-linecap="round" />
                        <path
                            d="M13.87 3.2C13.56 3.11 13.24 3.04 12.91 3C11.95 2.88 11.03 2.95 10.17 3.2C10.46 2.46 11.18 1.94 12.02 1.94C12.86 1.94 13.58 2.46 13.87 3.2Z"
                            stroke="#2AAF2F" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path
                            d="M15.02 19.06C15.02 20.71 13.67 22.06 12.02 22.06C11.2 22.06 10.44 21.72 9.89999 21.18C9.35999 20.64 9.01999 19.88 9.01999 19.06"
                            stroke="#2AAF2F" stroke-width="1.5" stroke-miterlimit="10" />
                    </svg>
                    <span
                        class="text-[#2AAF2F] text-[14px] font-normal leading-[25px] tracking-[0.5px] font-['Outfit']">{{ __('Security reminder: Please be wary of scam messages and links. :store will not ask for extra fees via SMS or email.', ['store' => $storeName]) }}</span>
                </div>

                {{-- Frame 1984079391: View + arrow-right, gap 4px, green --}}
                <a href="#"
                    class="flex items-center gap-1 text-[#2AAF2F] text-[14px] font-normal leading-[25px] tracking-[0.5px] font-['Outfit'] whitespace-nowrap hover:underline">
                    {{ __('View') }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="w-4 h-4 shrink-0">
                        <path stroke="#2AAF2F" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </section>
