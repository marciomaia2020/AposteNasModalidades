// Formatar inputs em moeda BRL
    document.querySelectorAll(".valor").forEach(input => {
      input.addEventListener("input", e => {
        let value = e.target.value.replace(/\D/g, "");
        value = (value/100).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
        e.target.value = value;
      });
    });

	

