const commands = new Map([
  ['help', { description: 'Show available commands.' }],
  ['projects list', { method: 'GET', path: '/api/v1/auth/projects', description: 'List accessible projects.' }],
  ['compute instances list', { method: 'GET', path: '/api/v1/compute/instances', description: 'List compute instances.' }],
  ['vm list', { method: 'GET', path: '/api/v1/vm/instances', description: 'List virtual machines.' }],
  ['kubernetes clusters list', { method: 'GET', path: '/api/v1/kubernetes/clusters', description: 'List Kubernetes clusters.' }],
  ['storage buckets list', { method: 'GET', path: '/api/v1/storage/buckets', description: 'List storage buckets.' }],
  ['network vpcs list', { method: 'GET', path: '/api/v1/network/vpcs', description: 'List VPCs.' }],
  ['database instances list', { method: 'GET', path: '/api/v1/database/instances', description: 'List database instances.' }]
]);

const output = document.getElementById('terminalOutput');
const form = document.getElementById('terminalForm');
const input = document.getElementById('terminalInput');

const write = (message, className = 'muted') => {
  const line = document.createElement('div');
  line.className = className;
  line.textContent = message;
  output.appendChild(line);
  output.scrollTop = output.scrollHeight;
};

const help = () => {
  write('Available commands:', 'muted');
  commands.forEach((definition, command) => {
    write(`  ${command.padEnd(30)} ${definition.description}`, 'muted');
  });
};

const execute = async (rawCommand) => {
  const command = rawCommand.trim().replace(/\s+/g, ' ');
  if (command.length === 0) {
    return;
  }

  write(`$ ${command}`, 'command');

  const definition = commands.get(command);
  if (!definition) {
    write(`Unknown command: ${command}. Type 'help' to list supported commands.`, 'error');
    return;
  }

  if (command === 'help') {
    help();
    return;
  }

  const response = await fetch(definition.path, {
    method: definition.method,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-Appwrite-Response-Format': '1.8.0'
    }
  });

  if (!response.ok) {
    write(`${definition.method} ${definition.path} failed with ${response.status}`, 'error');
    return;
  }

  const body = response.headers.get('content-type')?.includes('application/json')
    ? await response.json()
    : await response.text();

  write(typeof body === 'string' ? body : JSON.stringify(body, null, 2), 'muted');
};

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const command = input.value;
  input.value = '';

  try {
    await execute(command);
  } catch (error) {
    write(error instanceof Error ? error.message : 'Command failed.', 'error');
  }
});

document.querySelectorAll('[data-command]').forEach((button) => {
  button.addEventListener('click', () => {
    input.value = button.dataset.command;
    input.focus();
  });
});

write('NovaCloud terminal ready. Type help to list commands.');
