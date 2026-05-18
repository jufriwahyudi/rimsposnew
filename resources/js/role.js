const ROLE = {
    setSessionRole(roleid) {
        $.notify({
            title: 'Loading...',
            message: 'Mengubah role, harap tunggu...'
        }, {
            type: 'info'
        });
        fetch(`/role/set-session`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ role_id: roleid })
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    $.notify({
                        title: 'Berhasil!',
                        message: 'Role berhasil diubah!'
                    }, {
                        type: 'primary'
                    });
                    location.href = "/home";
                } else {
                    $.notify({
                        title: 'Gagal!',
                        message: 'Gagal mengubah role!'
                    }, {
                        type: 'danger'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
};
export default ROLE;