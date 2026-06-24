// fungsi untuk melakukan panggilan API dengan metode yang fleksibel ini waakk
async function apiCall(endpoint, method = 'GET', body = null) {
    const options = {
        method,
        headers: {}
    };

    if (body) {
        if (body instanceof FormData) {
            options.body = body;
        } else {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }
    }

    try {
        const response = await fetch(`api/${endpoint}`, options);

        if (response.status === 401) {
            alert('Sesi Anda telah berakhir. Anda akan diarahkan ke halaman login.');
            window.location.href = 'login.php';
            throw new Error('Unauthorized');
        }

        const responseText = await response.text();
        let result = null;
        try {
            result = responseText ? JSON.parse(responseText) : null;
        } catch (e) {
            throw new Error(`Respons server tidak valid (bukan JSON): ${responseText}`);
        }


        if (!response.ok) {
            if (result) {
                throw result;
            } else {
                throw new Error(`Terjadi kesalahan jaringan: ${response.statusText}`);
            }
        }

        return result;

    } catch (error) {
        console.error(`API Call Error (${method} ${endpoint}):`, error);
        throw error;
    }
}
