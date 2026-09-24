// Dashboard partner banner: "Invite a merchant" opens the native share sheet
// where available, otherwise copies the invite link.
import { toast } from '@tenant/core/toast.js';

export function mountPartnerInvite(root = document) {
    root.querySelectorAll('[data-partner-invite]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.dataset.inviteLink || '';
            const text = 'Join me as a merchant on NO GRGR';

            if (navigator.share) {
                try {
                    await navigator.share({ title: 'NO GRGR', text, url });
                    return;
                } catch (error) {
                    if (error?.name === 'AbortError') return; // user closed the sheet
                }
            }

            try {
                await navigator.clipboard.writeText(url);
                toast.success('Invite link copied');
            } catch {
                toast.error('Could not copy the invite link.');
            }
        });
    });
}
