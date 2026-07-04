const request = async (descriptor, payload = undefined) => {
  const [method, path] = descriptor.split(' ');
  const response = await fetch(path, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-Appwrite-Response-Format': '1.8.0'
    },
    credentials: 'include',
    body: payload === undefined ? undefined : JSON.stringify(payload)
  });

  if (!response.ok) {
    throw new Error(`NovaCloud settings request failed: ${method} ${path}`);
  }

  return response.headers.get('content-type')?.includes('application/json') ? response.json() : undefined;
};

const formPayload = (form) => Object.fromEntries(new FormData(form).entries());

document.querySelectorAll('form[data-endpoint]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;

    try {
      await request(form.dataset.endpoint, formPayload(form));
      submit.textContent = 'Saved';
    } catch (error) {
      submit.textContent = 'Retry';
      console.error(error);
    } finally {
      submit.disabled = false;
    }
  });
});

document.querySelectorAll('button[data-endpoint]').forEach((button) => {
  button.addEventListener('click', async () => {
    button.disabled = true;

    try {
      const payload = button.dataset.factor ? { factor: button.dataset.factor } : undefined;
      await request(button.dataset.endpoint, payload);
      button.textContent = 'Done';
    } catch (error) {
      button.textContent = 'Retry';
      console.error(error);
    } finally {
      button.disabled = false;
    }
  });
});
