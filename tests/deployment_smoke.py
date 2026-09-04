"""Run ONLY against an isolated, migrated test database: creates test records."""
import argparse
import datetime
import json
import urllib.request
import uuid

parser = argparse.ArgumentParser()
parser.add_argument('--base-url', default='http://127.0.0.1:8080')
parser.add_argument('--allow-writes', action='store_true')
args = parser.parse_args()
if not args.allow_writes:
    parser.error('--allow-writes is required; use an isolated test deployment')

count = 0
def request(path, data=None):
    global count
    body = json.dumps(data).encode() if data is not None else None
    req = urllib.request.Request(args.base_url + '/' + path, data=body,
                                 headers={'Content-Type': 'application/json'})
    with urllib.request.urlopen(req, timeout=15) as response:
        result = response.read()
        assert response.status == 200, path
        count += 1
        if 'application/json' in response.headers.get('Content-Type', ''):
            result = json.loads(result)
            assert result.get('status') != 'error', (path, result)
    print('PASS', path or '/')
    return result

for path in ['', 'accounts', 'history', 'metrics', 'config', 'printing',
             'sales', 'sales/create', 'sales/debts', 'inventory',
             'inventory/items', 'inventory/movements', 'audit', 'ai',
             'transaction/stats', 'sales/get-statuses', 'sales/get-active-orders']:
    request(path)

tag = 'Deploy test ' + uuid.uuid4().hex[:8]
request('accounts/add', {'name': tag, 'balance': 1000, 'currency': 'Bs'})
accounts = request('accounts/fetch')['data']
account = next(a for a in accounts if a['name'] == tag)
account_id = int(account['id'])
category = request('config/get-data')['categories'][0]['id']
transaction = request('transaction/save', {
    'account_id': account_id, 'category_id': category, 'type': 'expense',
    'owner': 'Negocio', 'amount': 20, 'amount_usd': 0.4, 'exchange_rate': 50,
    'description': tag, 'items': [{'name': 'Paper', 'description': tag,
                                  'quantity': 2, 'price': 10, 'price_usd': 0.2}]})
items = request('history/items/' + str(transaction['id']))
assert len(items['items']) == 1
assert float(items['items'][0]['total']) == 20
accounts = request('accounts/fetch')['data']
assert float(next(a for a in accounts if int(a['id']) == account_id)['balance']) == 980
request('accounts/create-temp', {'source_id': account_id, 'amount': 10, 'name': tag + ' temp'})
accounts = request('accounts/fetch')['data']
temporary = next(a for a in accounts if a['name'] == tag + ' temp')
request('accounts/close-temp/' + str(temporary['id']))
request('accounts/transfer', {'source_id': account_id, 'dest_id': accounts[0]['id'],
                             'amount': 5, 'category_id': category})

request('config/save', {'key': 'deployment_test', 'value': tag})
request('config/save', {'key': 'deployment_test', 'value': tag + ' updated'})
request('inventory/save-item', {'name': tag, 'category_id': None, 'price': 1,
                                'cost': 0.5, 'unit': 'unid', 'stock': 10})
inventory = request('inventory/get-items')['data']
item_id = next(i['id'] for i in inventory if i['name'] == tag)
today = datetime.date.today().isoformat()
request('sales/store', {'customer': tag, 'date': today, 'exchange_rate': 50,
                        'status': 'partial', 'paid_amount': 25, 'paid_amount_usd': 0.5,
                        'account_id': account_id, 'category_id': category,
                        'items': [{'id': item_id, 'quantity': 1, 'price_usd': 1, 'price_bs': 50}]})
inventory = request('inventory/get-items')['data']
assert float(next(i for i in inventory if i['id'] == item_id)['stock']) == 9
request('sales')
sales = request('sales/get-active-orders')['data']
sale_id = next(s['id'] for s in sales if s['customer'] == tag)
request('sales/add-payment', {'sale_id': sale_id, 'amount': 25, 'amount_usd': 0.5,
                              'rate': 50, 'date': today, 'reference': tag})
request('sales/get-details/' + str(sale_id))
request('sales/history')
request('printing/store', {'customer_name': tag, 'product_name': 'Copia B/N',
                           'quantity': 2, 'price_bs': 2, 'price_usd': 0,
                           'paid_bs': 4, 'paid_usd': 0, 'exchange_rate': 50,
                           'account_id': account_id})
request('printing/history')
request('printing/toggle-favorite', {'name': tag, 'favorite': True})
customers = request('printing/customers?term=' + urllib.parse.quote(tag))['data']
assert any(c['name'] == tag and int(c['is_favorite']) == 1 for c in customers)
conversation = request('ai/save-conversation', {
    'title': tag, 'messages': [{'role': 'user', 'content': 'Deployment test'}]})
request('ai/conversation/' + str(conversation['id']))
request('ai/conversations')
request('history/fetch', {'search': tag})
metrics = request('metrics/fetch', {'start': today, 'end': today})
assert float(metrics['data']['totals']['expense']) >= 20
assert metrics['data']['by_category']
request('audit/fetch', {})
request('config/export')
request('metrics/export')
print(f'{count} HTTP checks passed, including writes and balance assertions.')
