library(immunarch)

# test for arguments
args = commandArgs(trailingOnly=TRUE)
if (length(args)!=2) {
  stop("An input and output directory must be supplied.n", call.=FALSE)
}

# Get the input and output directories
input_dir = args[1]
output_dir = args[2]

# load data. Need a separate tsv per repertoire. Metadata file is optional, but allows for grouped comparisons.
#data <- repLoad("/project/6008066/ireceptor/analysis_apps/singularity/immunarch/data/igh_multi")
data <- repLoad(input_dir)

### exploratory analysis. The repExplore function calculates the basic statistics of repertoire: the number of unique immune receptor clonotypes, their relative abundances, and sequence length distribution across the input dataset.
# repExplore(.data, .method = c("volume", "count", "len", "clones"), .col = c("nt", "aa"), .coding = TRUE)

# number of unique clonotypes (rearrangements??)
exp_vol <- repExplore(data$data, .method = "volume")
exp_vol_vis <- vis(exp_vol)
ggsave(paste(output_dir, "vol.pdf", sep="/"), plot = exp_vol_vis, device = "pdf")

# distribution of clonotype abundances, i.e., how frequent receptors with different abundances are
exp_count <- repExplore(data$data, .method = "count")
exp_count_vis <- vis(exp_count)
ggsave(paste(output_dir, "count.pdf", sep="/"), plot = exp_count_vis, device = "pdf")

# distribution of CDR3 sequence lengths
exp_len <- repExplore(data$data, .method = "len")
exp_len_vis <- vis(exp_len)
ggsave(paste(output_dir, "len.pdf", sep="/"), plot = exp_len_vis, device = "pdf")

# distribution of CDR3 sequence lengths, by status
#exp_len_grp <- repExplore(data$data, .method = "len")
#exp_len_grp_vis <- vis(exp_len, .by = c("Status"), .meta = data$meta)
#ggsave(paste(output_dir, "len_grp.pdf", sep="/"), plot = exp_len_grp_vis, device = "pdf")

# number of clones (i.e., cells) per input repertoire
print('clones.pdf')
exp_clon <- repExplore(data$data, .method = "clones")
exp_clon_vis <- vis(exp_clon)
ggsave(paste(output_dir, "clones.pdf", sep="/"), plot = exp_clon_vis, device = "pdf")

### Clonality analyis. repClonality function encompasses several methods to measure clonal proportions in a given repertoire.
# repClonality(.data,.method = c("clonal.prop", "homeo", "top", "rare"), .perc = 10, .clone.types = c(Rare = 1e-05, Small = 1e-04, Medium = 0.001, Large = 0.01, Hyperexpanded = 1), .head = c(10, 100, 1000, 3000, 10000, 30000, 1e+05), .bound = c(1, 3, 10, 30, 100))

# clonal proportions or in other words percentage of clonotypes required to occupy specified by .perc percent of the total immune repertoire.
print("clonal_prop.pdf")
clon_prop <- repClonality(data$data, .method = "clonal.prop")
clon_prop_vis <- vis(clon_prop)
ggsave(paste(output_dir, "clonal_prop.pdf", sep="/"), plot = clon_prop_vis, device = "pdf")

# estimate relative abundance for the groups of top clonotypes in repertoire, e.g., ten most abundant clonotypes. Use ".head" to define index intervals, such as 10, 100 and so on.
#print("clonal_top.pdf")
#clon_top <- repClonality(data$data, .method = "top")
#clon_top_vis <- vis(clon_top)
#ggsave(paste(output_dir, "clonal_top.pdf", sep="/"), plot = clon_top_vis, device = "pdf")

# relative abundance for the groups of rare clonotypes with low counts. Use ".bound" to define the threshold of clonotype groups.
print("clonal_rare.pdf")
clon_rare <- repClonality(data$data, .method = "rare")
clon_rare_vis <- vis(clon_rare)
ggsave(paste(output_dir, "clonal_rare.pdf", sep="/"), plot = clon_rare_vis, device = "pdf")

# relative abundance (also known as clonal space homeostasis), which is defined as the proportion of repertoire occupied by clonal groups with specific abundances.
print("clonal_homeo.pdf")
clon_hom <- repClonality(data$data, .method = "homeo")
clon_hom_vis <- vis(clon_hom)
ggsave(paste(output_dir, "clonal_homeo.pdf", sep="/"), plot = clon_hom_vis, device = "pdf")

### diversity analysis. Estimate the diversity of species or objects in the given distribution
# repDiversity(.data, .method = "chao1", .col = "aa", .max.q = 6, .min.q = 1, .q = 5, .step = NA, .quantile = c(0.025, 0.975), .extrapolation = NA, .perc = 50, .norm = TRUE, .verbose = TRUE, do.norm = NA, .laplace = 0)

# chao1
print("div_chao.pdf")
div_chao <- repDiversity(data$data, .method = "chao1")
div_chao_vis <- vis(div_chao)
ggsave(paste(output_dir, "div_chao.pdf", sep="/"), plot = div_chao_vis, device = "pdf")

# hill
print("div_hill.pdf")
div_hill <- repDiversity(data$data, .method = "hill")
div_hill_vis <- vis(div_hill)
ggsave(paste(output_dir, "div_hill.pdf", sep="/"), plot = div_hill_vis, device = "pdf")

# true diversity
print("div_true-div.pdf")
div_trdiv <- repDiversity(data$data, .method = "div")
div_trdiv_vis <- vis(div_trdiv)
ggsave(paste(output_dir, "div_true-div.pdf", sep="/"), plot = div_trdiv_vis, device = "pdf")

# gini-simpson
print("div_gini-simpson.pdf")
div_ginsimp <- repDiversity(data$data, .method = "gini.simp")
div_ginsimp_vis <- vis(div_ginsimp)
ggsave(paste(output_dir, "div_gini-simpson.pdf", sep="/"), plot = div_ginsimp_vis, device = "pdf")

# inverse simpson
print("div_inverse-simpson.pdf")
div_invsimp <- repDiversity(data$data, .method = "inv.simp")
div_invsimp_vis <- vis(div_invsimp)
ggsave(paste(output_dir, "div_inverse-simpson.pdf", sep="/"), plot = div_invsimp_vis, device = "pdf")

# gini coefficient - commented out because this does not produce a plot, just a matrix
#div_gini <- repDiversity(data$data, .method = "gini")
#div_gini_vis <- vis(div_gini)
#ggsave("div_gini-coeff.pdf", plot = div_gini_vis, device = "pdf")

# d50
print("div_d50.pdf")
div_d50 <- repDiversity(data$data, .method = "d50")
div_d50_vis <- vis(div_d50)
ggsave(paste(output_dir, "div_d50.pdf", sep="/"), plot = div_d50_vis, device = "pdf")

### gene usage. 
# geneUsage(.data, .gene = c("hs.trbv", "HomoSapiens.TRBJ", "macmul.IGHV"), .quant = c(NA, "count"), .ambig = c("inc", "exc", "maj"), .type = c("segment", "allele", "family"), .norm = FALSE)

# gene segment usage, normalized
print("gene_usage_normalized.pdf")
gu_norm <- geneUsage(data$data, .norm = T)
gu_norm_vis <- vis(gu_norm)
ggsave(paste(output_dir, "gene_usage_normalized.pdf", sep="/"), plot = gu_norm_vis, device = "pdf")

# gene family usage, normalized
print("gene_family_usage_normalized.pdf")
gu_fam_norm <- geneUsage(data$data, .norm = T, .type = "family")
gu_fam_norm_vis <- vis(gu_fam_norm)
ggsave(paste(output_dir, "gene_family_usage_normalized.pdf", sep="/"), plot = gu_fam_norm_vis, device = "pdf")

# gene segment usage, normalized, grouped by status
#gu_norm_grp <- geneUsage(data$data, .norm = T)
#gu_norm_grp_vis <- vis(gu_norm_grp, .by = c("Status"), .meta = data$meta)
#ggsave(paste(output_dir, "gene_usage_grouped_normalized.pdf", sep="/"), plot = gu_norm_grp_vis, device = "pdf")

# gene family usage, normalized, grouped by status
#gu_fam_norm_grp <- geneUsage(data$data, .norm = T, .type = "family")
#gu_fam_norm_grp_vis <- vis(gu_fam_norm_grp, .by = c("Status"), .meta = data$meta)
#ggsave(paste(output_dir, "gene_family_usage_grouped_normalized.pdf", sep="/"), plot = gu_fam_norm_grp_vis, device = "pdf")

### overlap analysis

# jaccard index. Measures the similarity between finite sample sets, and is defined as the size of the intersection divided by the size of the union of the sample sets.
print("overlap-jaccard.pdf")
ov_jacc <- repOverlap(data$data, .method = "jaccard", .verbose = FALSE)
ov_jacc_vis <- vis(ov_jacc)
ggsave(paste(output_dir, "overlap-jaccard.pdf", sep="/"), plot = ov_jacc_vis, device = "pdf")

# overlap coefficient. A normalised measure of overlap similarity. It is defined as the size of the intersection divided by the smaller of the size of the two sets.
print("overlap.pdf")
ov <- repOverlap(data$data, .method = "overlap", .verbose = FALSE)
ov_vis <- vis(ov)
ggsave(paste(output_dir, "overlap.pdf", sep="/"), plot = ov_vis, device = "pdf")

# number of public (shared) clonotypes
#print("overlap-public.pdf")
#ov_pub <- repOverlap(data$data, .method = "public", .verbose = FALSE)
#ov_pub_vis <- vis(ov_pub)
#ggsave(paste(output_dir, "overlap-public.pdf", sep="/"), plot = ov_pub_vis, device = "pdf")
