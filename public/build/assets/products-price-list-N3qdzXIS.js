function e(e,t,n,r){let i=Number(e)||0,a=Math.max(0,Number(n)||0),o=Number(r)||0,s=t===`fixed`?a:i*a/100;return Math.round((i+s+o)*100)/100}function t(t,n,r){let i={};return Object.entries(n||{}).forEach(([n,a])=>{i[n]=e(t,(a?.type||`percentage`)===`fixed`?`fixed`:`percentage`,Math.max(0,Number(a?.value)||0),Number((r||{})[n]??0))}),i}function n(e){return`$${Number(e||0).toFixed(2)}`}function r(e,t){return e[t]||`Country #${t}`}function i(t,{basePrice:i,profits:a,shippingByCountry:o,countryLabels:s},c){t.innerHTML=``,Object.keys(a).forEach(l=>{let u=a[l],d=Number(o[l]??0),f=document.createElement(`tr`),p=e(i,u.type,u.value,d);f.innerHTML=`
            <td>${r(s,l)}${l===`default`?` <span class="badge badge-amber price-list-fallback-badge">Fallback</span>`:``}</td>
            <td>${n(i)}</td>
            <td>
                <div class="price-list-profit-cell">
                    <select class="field-control" data-profit-type>
                        <option value="percentage" ${u.type===`fixed`?``:`selected`}>% of price</option>
                        <option value="fixed" ${u.type===`fixed`?`selected`:``}>Fixed ($)</option>
                    </select>
                    <input type="number" step="0.01" min="0" class="field-control" data-profit-value value="${u.value}">
                </div>
            </td>
            <td>${n(d)}</td>
            <td class="price-list-your-price" data-your-price>${n(p)}</td>
        `;let m=f.querySelector(`[data-profit-type]`),h=f.querySelector(`[data-profit-value]`),g=f.querySelector(`[data-your-price]`),_=()=>{u.type=m.value,u.value=parseFloat(h.value)||0,g.textContent=n(e(i,u.type,u.value,d)),c?.()};m.addEventListener(`change`,_),h.addEventListener(`input`,_),t.appendChild(f)})}export{t as computePrices,i as renderPriceTable};