we need to make .md file as formula and calculation of this project with these calculations :

order_grand_total = items_grand_total + shipping - discount from coupon if exists
items_grand_total = sum (item_grand_total) of all items
item_grand_total = sub_total  + fixed cost price - discount amount (from flash sale if exists) + tax
sub_total = price * qty
price = product/variant price + profit amount (percentage|fixed)

---------------------------------------------- Customer website example  ---------------------------------
example we add a product for example P1 to cart with this product details
lets speak about this product http://vendor.dokan-v2.loc/admin/products/144/edit
i will select/add to cart this variant "New hot sale 65 pieces cost" with qty = 1

we will open website of vendor/tenant with "United Kingdom" country and USD Currency

we have flash sale contains this product with 10% discount percentage
we dont have active coupon code = 0
cost price (price come from tenant) = 1.72
price of product = cost_price + profit_amount = 1.72 + (1.72*25)/100 = 2.15
sub_total = price * qty = 2.15 * 1 = 2.15
item_grand_total = sub_total  + fixed cost price - discount amount (from flash sale if exists) + tax
		 = 2.15 + 200 - ((2.15 + 200)*10)/100 + 0 or whatever calculated before = 181.935
order_grand_total= sum(item_grand_total) + shipping - discount from coupon if exists
		 = 181.935 + 1.2 (country shipping for this product (calculated by weight -> take current logic)) + 0 = 183.135

---------------------------------------- Vendor panel ---------------------------------
example we add a product for example P1 to cart with this product details
lets speak about this product http://vendor.dokan-v2.loc/admin/products/144/edit
i will select/add to cart this variant "New hot sale 65 pieces cost" with qty = 1
take same calculations into "Customer website example" and add that

-- platform_profit (the amount which be paid in USD from vendor to platform and this calculations happened when amount paid to gateway of vendor) = sum ((item.cost_price * item.qty) + item.fixed_cost_price - item.discount (if flash sale from central panel not created by vendor , if not created by admin then discount will be 0)) + order.shipping + order.tax - order.discount (if coupon code from central panel not created by vendor , if not created by admin then discount will be 0)

-- vendor_own_platform = platform_profit - sum (transactions.amount)

--  vendor_profit (the amount which be paid in USD from platform to vendor and this calculations happened when amount paid to gateway of central) = sum((item.profit_amount * item.qty) - discount (if flash sale created by vendor not central)) - order.discount (if coupon code created by vendor not central admin)

-- platform_own_vendor = vendor_profit - sum (transactions.amount)

ability to pay to admin -> same action which into order details page

---------------------------------------- Central/Admin panel ---------------------------------

same of Vendor panel calculations and equations

------------------------------------------------------------
orders table : remove both columns (vendor_cost & owner_profit) from all platform panels and calculations and everything else ,, and add/replace with new columns (vendor_profit & platform_profit and value will be same of above which explained into vendor panel)

order_items : need to add columns like (cost (cost price of product/variant) , profit_amount , qty , price (cost + profit)  , sub_total (price*qty) , fixed_shipping , tax , shipping_fee , grand_total (sub_total + fixed_shipping + tax + shipping_fee (same logic of shipping fee which exists already)) , weight (same logic which created))

order items columns i mintioned now i need it to be same columns sorting into table and also add what not exists into table columns

------------------------------------------------------------
you are a Team leader and business analyzer , we need to refactor everything related with orders and calculation to be same above , and you have teammates (Fullstack developer + QA tester) working as agents/sub-agents with you ,, make everything working together without errors and give me summary at the end ,, generate .md file with what will plan ,, and another to save what done and before get max limit of usage commit everything and update .md files to continue after usage limit open

-----------------------------
change everything related with orders into vendor panel and central/admin panel & storefront page & storefront profile and order details pages inside all variants & themes

 ,, raed first this vendor/tenant database /var/www/multi-vendor/backup/dokan_v2_e57e205f-15db-4d10-ab95-0214eba0d8b5.sql then plan everything and create .md files then start to fix and make changes , commit everything after done of each task ,, dont forget to refactor columns and keys which into order details into both panels (central/admin panel + vendor/tenant panel)
